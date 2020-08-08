<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Debat;
use App\Entity\Intervenant;
use App\Entity\Loi;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Themes
        $themesArray = array(
            ['Constitution', '#c5b0d5'],
            ['Travaux, énergie, transports', '#9467bd'],
            ['Droit privé, procédure civile', '#ff9896'],
            ['Procédure pénale, exécution', '#d62728'],
            ['Sécurité', '#98df8a'],
            ['Ecole, science, culture', '#2ca02c'],
            ['Etat, peuple, autorités', '#ffbb78'],
            ['Santé, sécurité sociale', '#ff7f0e'],
            ['Finance et impôts', '#aec7e8'],
            ['Economie', '#1f77b4'],
        );

        $firstTheme = null;
        $secondTheme = null;
        $fourthTheme = null;
        $seventhTheme = null;
        foreach ($themesArray as $key => $themeArray) {
            $theme = new Theme();
            $theme->setNom($themeArray[0]);
            $theme->setCouleur($themeArray[1]);
            $manager->persist($theme);

            if ($key == 0)
                $firstTheme = $theme;

            if ($key == 1)
                $secondTheme = $theme;

            if ($key == 3)
                $fourthTheme = $theme;

            if ($key == 6)
                $seventhTheme = $theme;
        }

        // Articles
        $article = new Article();
        $article->setTheme($firstTheme);
        $article->setTexte('<sup>1</sup> Le Valais est une république démocratique, souveraine dans les limites de la Constitution fédérale et incorporée comme canton à la Confédération suisse.<br/><br/><sup>2</sup> La souveraineté réside dans le peuple. Elle est exercée, directement par les électeurs et indirectement par les autorités constituées.');
        $article->setDateSeance(new \DateTime('1906-02-19'));
        $article->setNomArticle(1);
        $article->setCommission(1);
        $article->setImage('https://iphonesoft.fr/images/_082019/fond-ecran-dynamique-macos-wallpaper-club.jpg');
        $manager->persist($article);

        $article2 = new Article();
        $article2->setTheme($firstTheme);
        $article2->setTexte('<sup>1</sup> La liberté de conscience, de croyance et de libre exercice du culte sont garantis.<br/><br/><sup>2</sup> Les communautés religieuses définissent leur doctrine et aménagent leur culte en toute indépendance. Elles s\'organisent et s\'administrent d\'une manière autonome, dans les limites du droit public.<br/><br/><sup>3</sup> Le statut de personne juridique de droit public est reconnu à l\'Eglise catholique romaine et à l\'Eglise réformée évangélique. Les autres confessions sont soumises aux règles du droit privé; la loi peut leur conférer un statut de droit public pour tenir compte de leur importance sur le plan cantonal.<br/><br/><sup>4</sup> Pour autant que les paroisses de l\'Eglise catholique romaine et celles de l\'Eglise réformée évangélique ne peuvent, par leurs moyens propres, subvenir aux frais de culte des Eglises locales, ceux-ci sont, sous réserve des libertés de conscience et de croyance, mis à la charge des communes municipales. Le canton peut allouer des subventions aux Eglises reconnues de droit public.<br/><br/><sup>5</sup> La loi règle l\'application des présentes dispositions.');
        $article2->setDateSeance(new \DateTime('1906-02-20'));
        $article2->setNomArticle(2);
        $article2->setCommission(1);
        $article2->setImage('https://steamuserimages-a.akamaihd.net/ugc/940586530515504757/CDDE77CB810474E1C07B945E40AE4713141AFD76/');
        $manager->persist($article2);

        // Intervenants
        $intervenantsArray = array(
            ['M.', 'Roten', 'Henri'],
            ['M.', 'Merio', 'Roger'],
            ['M.', 'Delacoste', 'Edmond'],
            ['M.', 'De Torrente', 'Henri'],
            ['M.', 'Evequoz', 'Raymond'],
            ['M.', 'Troillet', 'Maurice'],
            ['M.', 'Kluser', ''],
            ['M.', 'Couchepin', ''],
            ['M.', 'Bioley', ''],
            ['M.', 'Delacoste', ''],
            ['M.', 'Plissier', ''],
            ['M.', 'Bressoud', ''],
            ['M.', 'De Riedmatten', 'Raoul'],
            ['M.', 'Burgener', ''],
            ['M.', 'De Werra', ''],
            ['M.', 'De Riedmatten', 'Jacques'],
            ['M.', 'Arlettaz', ''],
            ['M.', 'Loretan', ''],
            ['M.', 'Defayes', ''],
            ['M.', 'Zen-Ruffinen', ''],
            ['M.', 'Burgener', ''],
            ['M.', 'Roten', ''],
            ['M.', 'Kluser', ''],
            ['M.', 'Pignat', ''],
            ['M.', 'Abbet', ''],
            ['M.', 'Biley', ''],
            ['M.', 'Kuntschen', ''],
            ['M.', 'Stockalper', ''],
        );

        foreach ($intervenantsArray as $intervenantArray) {
            $intervenant = new Intervenant();
            $intervenant->setTitre($intervenantArray[0]);
            $intervenant->setNom($intervenantArray[1]);
            $intervenant->setPrenom($intervenantArray[2]);
            $manager->persist($intervenant);
        }

        // Lois
        $loi = new Loi();
        $loi->setTheme($secondTheme);
        $loi->setNomLoi('170.2');
        $loi->addArticle($article);
        $manager->persist($loi);

        $loi2 = new Loi();
        $loi2->setTheme($seventhTheme);
        $loi2->setNomLoi('180.1');
        $loi2->addArticle($article2);
        $manager->persist($loi2);

        $loi3 = new Loi();
        $loi3->setTheme($fourthTheme);
        $loi3->setNomLoi('400.1');
        $loi3->addArticle($article2);
        $manager->persist($loi3);

        // Debats
        $debat = new Debat();
        $debat->setIntervenant(null);
        $debat->setTexte('En ajoutant le mot « démocratique » au Premier alinéa, la Commission n\'a pas eu l\'idée d\'innover. Comme vous, elle sait que la République du Valais a été démocratique sous l\'empire de la Constitution actuelle ; mais c\'est précisément parce que ce qualificatif ou plutôt cette qualité nécessaire ä une vraie république répond ä la réalité, qu\'il a paru opportun ä la Commission de Adopter. II est du reste incontestable que la Constitution projetée renferme en soi les Principes d\'une évolution vers la démocratie pure, et c\'est aussi pour reconclure ä cette idée que la Commission propose cette adjonction. II a paru nécessaire ä la Commission de donner une autre tournure ä l’alinéa 3 de l\'article 1; car s\'il était exact de dire sous l\'empire de la Constitution actuelle que la forme du gouvernement est celle  de la démocratie représentative, sous réserve des  droits attribues au peuple, il est -plus juste et plus exact de dire avec la nouvelle Constitution que le peuple fait usage lui-même des attributs de la souveraineté, sous réserve des droits qu\'il confère aux autorités constituées.');
        $debat->addArticle($article);
        $manager->persist($debat);

        $manager->flush();
    }
}
