<?php

namespace App\Controller;

use App\Entity\Archive;
use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Debat;
use App\Entity\Intervenant;
use App\Entity\Loi;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 * @package App\Controller
 * @Route("/constitutions-time-machine/boussole")
 */
class DefaultController extends AbstractController
{
    private $colors = array(
        ['#9467bd', 'white'],
        ['#d62728', 'white'],
        ['#2ca02c', 'white'],
        ['#ff7f0e', 'white'],
        ['#0a0075', 'white'],
        ['#1f77b4', 'white'],
        ['#7E0000', 'white'],
    );

    /**
     * @Route("/", name="index")
     */
    public function index(EntityManagerInterface $em)
    {
        $articles = $em->getRepository(Article::class)->findAll();

        return $this->render('index.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/inserts", name="inserts")
     */
    public function inserts(EntityManagerInterface $em)
    {
        $em->getRepository(Article::class)->insertCommissions();
        $em->getRepository(Article::class)->insertThemes();
        $em->getRepository(Article::class)->insertArticles();
        $em->getRepository(Article::class)->insertArchives();
        $em->getRepository(Article::class)->insertIntervenants();
        $em->getRepository(Article::class)->insertLois();
        $em->getRepository(Article::class)->insertArticlesLois();
        $em->getRepository(Article::class)->insertDebats();
        $em->getRepository(Article::class)->insertDebatsIntervenants();
    }

    /**
     * @Route("/constitution", name="constitution")
     */
    public function constitution()
    {
        return $this->render('constitution.html.twig');
    }

    /**
     * @Route("/search", name="search")
     */
    public function search(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator, SessionInterface $session)
    {
        $page = $request->get('page') !== null && $request->get('page') !== '' ? $request->get('page') : 1;
        $sort_by = $request->get('sort_by') !== null && $request->get('sort_by') !== '' ? $request->get('sort_by') : 'article_number';
        $order = $request->get('order') !== null && $request->get('order') !== '' ? $request->get('order') : 'asc';
        $term = $request->get('term') !== null && $request->get('term') !== '' ? $request->get('term') : '';

        if ($term !== '') {
            $session->set('themes', []);
            $session->set('commissions', []);
            $session->set('intervenants', []);
            $themesSearch = array();
            $commissionsSearch = array();
            $intervenantsSearch = array();
        } else {
            $themesSearch = $session->get('themes') !== null && $session->get('themes') !== [] ? $session->get('themes') : [];
            $commissionsSearch = $session->get('commissions') !== null && $session->get('commissions') !== [] ? $session->get('commissions') : [];
            $intervenantsSearch = $session->get('intervenants') !== null && $session->get('intervenants') !== [] ? $session->get('intervenants') : [];
        }

        if (isset($_POST['submit'])) {
            if (isset($_POST['themes']) && !empty($_POST['themes']))
                $themesSearch = $_POST['themes'];
            else
                $themesSearch = [];

            if (isset($_POST['commissions']) && !empty($_POST['commissions']))
                $commissionsSearch = $_POST['commissions'];
            else
                $commissionsSearch = [];

            if (isset($_POST['intervenants']) && !empty($_POST['intervenants']))
                $intervenantsSearch = $_POST['intervenants'];
            else
                $intervenantsSearch = [];

            $session->set('themes', $themesSearch);
            $session->set('commissions', $commissionsSearch);
            $session->set('intervenants', $intervenantsSearch);
            $page = 1;
        }

        $queryBuilder = $em->getRepository(Article::class)->searchQueryBuilder($term, $sort_by, $order, $themesSearch, $commissionsSearch, $intervenantsSearch);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            10
        );

        $articles = $queryBuilder->getQuery()->getResult();
        $count = sizeof($articles);

        $nbPages = ceil(sizeof($articles) / 10);

        $allArticles = $em->getRepository(Article::class)->findAll();

        $articles = $term === '' ? $allArticles : $articles;

        $themes = array();
        $nbThemes = array();
        $intervenants = array();
        $nbIntervenants = array();
        $commissions = $em->getRepository(Commission::class)->findAll();
        $nbCommissions = array();

        foreach ($articles as $article) {
            $theme = $article->getTheme();
            if (!in_array($theme, $themes)) {
                $themes[$theme->getId()] = $theme;
                $nbThemes[$theme->getId()] = 1;
            } else {
                $nbThemes[$theme->getId()] += 1;
            }

            $tempThemes = array();
            foreach ($article->getLois() as $loi) {
                $theme = $loi->getTheme();
                if (!in_array($theme->getId(), $tempThemes)) {
                    if (!in_array($theme, $themes)) {
                        array_push($themes, $theme);
                        $nbThemes[$theme->getId()] = 1;
                    } else
                        $nbThemes[$theme->getId()] += 1;
                    $tempThemes[] = $theme->getId();
                }
            }

            foreach ($article->getDebats() as $debat) {
                if (sizeof($debat->getIntervenants()) > 0) {
                    foreach ($debat->getIntervenants() as $intervenant) {
                        if (!in_array($intervenant, $intervenants)) {

                            $intervenants[$intervenant->getId()] = $intervenant;
                            $nbIntervenants[$intervenant->getId()] = 1;
                        } else
                            $nbIntervenants[$intervenant->getId()] += 1;
                    }
                }
            }

            if ($article->getCommission() !== null) {
                $commission = $article->getCommission()->getId();
                if (in_array($commission, array_keys($nbCommissions)))
                    $nbCommissions[$commission] += 1;
                else
                    $nbCommissions[$commission] = 1;
            }
        }

        arsort($nbThemes);
        arsort($nbIntervenants);
        arsort($nbCommissions);

        return $this->render('search.html.twig', [
            'pagination' => $pagination,
            'articles' => $articles,
            'themes' => $themes,
            'nbThemes' => $nbThemes,
            'intervenants' => $intervenants,
            'nbIntervenants' => $nbIntervenants,
            'commissions' => $commissions,
            'nbCommissions' => $nbCommissions,
            'page' => $page,
            'sort_by' => $sort_by,
            'order' => $order,
            'term' => $term,
            'nbPages' => $nbPages,
            'count' => $count
        ]);
    }

    /**
     * @Route("/commission", name="commission-index")
     */
    public function commission(EntityManagerInterface $em)
    {
        $commissions = $em->getRepository(Commission::class)->findAll();

        return $this->render('commission/index.html.twig', [
            'commissions' => $commissions
        ]);
    }

    /**
     * @Route("/commission/details/{id}", name="commission-details")
     */
    public function detailsCommission(Commission $commission, EntityManagerInterface $em)
    {
        return $this->render('commission/details.html.twig', [
            'commission' => $commission
        ]);
    }

    /**
     * @Route("/article/details/{id}", name="article-details")
     */
    public function details($id, EntityManagerInterface $em)
    {
        $article = $em->getRepository(Article::class)->find($id);

        $lois = $em->getRepository(Loi::class)->findByArticleAndOrderByLoi($article->getId());
        $archives = $em->getRepository(Archive::class)->findByArticleAndOrderByDateDecision($article->getId());
        $debats = $em->getRepository(Debat::class)->findByArticleAndOrderByDateSeance($article->getId());

        return $this->render('details.html.twig', [
            'article' => $article,
            'colors' => $this->colors,
            'lois' => $lois,
            'archives' => $archives,
            'debats' => $debats
        ]);
    }

    /**
     * @Route("/loi/details/{id}", name="loi-details")
     */
    public function loi($id, EntityManagerInterface $em)
    {
        return $this->render('loi/details.html.twig', [
            'id' => $id
        ]);
    }

    /**
     * @Route("/intervenant", name="intervenant-index")
     */
    public function intervenant(EntityManagerInterface $em)
    {
        $intervenants = $em->getRepository(Intervenant::class)->findBy([], ['nom' => 'asc']);

        usort($intervenants, function ($a, $b) {
            if (($a->getPhoto() === null && $b->getPhoto() === null) || ($a->getPhoto() !== null && $b->getPhoto() !== null)) {
                if ($a->getNom() < $b->getNom())
                    return -1;
                else
                    return 1;
            }
            if ($a->getPhoto() === null)
                return 1;
            if ($b->getPhoto() === null)
                return -1;

            return 0;
        });

        return $this->render('intervenant/index.html.twig', [
            'intervenants' => $intervenants
        ]);
    }

    /**
     * @Route("/intervenant/details/{id}", name="intervenant-details")
     */
    public function detailsIntervenant($id, EntityManagerInterface $em)
    {
        $intervenant = $em->getRepository(Intervenant::class)->find($id);

        $commissions = array();
        foreach ($intervenant->getDebats() as $debat) {
            $commissions[$debat->getArticle()->getCommission()->getId()][] = $debat;
        }

        $newCommissions = array();
        foreach ($commissions as $key => $debats) {
            usort($debats, function ($a, $b) {
                if ($a->getDateSeance() < $b->getDateSeance())
                    return -1;
                else if ($a->getDateSeance() == $b->getDateSeance()) {
                    if ($a->getArticle()->getNomArticle() < $b->getArticle()->getNomArticle()) {
                        return 1;
                    } else
                        return -1;
                } else return 1;
            });

            $newCommissions[$key] = $debats;
        }

        return $this->render('intervenant/details.html.twig', [
            'intervenant' => $intervenant,
            'commissions' => $newCommissions
        ]);
    }

    /**
     * @Route("/debat/details/{id}", name="debat-details")
     */
    public function detailsDebat($id, EntityManagerInterface $em)
    {
        $debat = $em->getRepository(Debat::class)->find($id);

        return $this->render('debat/details.html.twig', [
            'debat' => $debat
        ]);
    }

    /**
     * @Route("/ajax/autocomplete", name="debat-details", options={"expose"=true})
     */
    public function ajaxAutocomplete(Request $request, EntityManagerInterface $em)
    {
        $term = $request->get('term');
        $articles = $em->getRepository(Article::class)->searchQueryBuilder($term)->getQuery()->getResult();

        $arrayArticles = array();
        foreach ($articles as $article) {
            $tempArticle = array(
                'id' => $article->getId(),
                'texte' => $article->getTexte() !== null ? $article->getTexte() : 'Article abrogé',
                'nomArticle' => $article->getNomArticle(),
                'nbDebats' => sizeof($article->getDebats()),
                'nbCitations' => sizeof($article->getLois()),
                'nbModifications' => sizeof($article->getArchives()),
                'nomCommission' => $article->getCommission() ? $article->getCommission()->getNumero() . ' - ' . $article->getCommission()->getNom() : 'Pas de commission',
            );

            array_push($arrayArticles, $tempArticle);
        }

        $response = new JsonResponse($arrayArticles);

        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}