<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function searchQueryBuilder(string $term = '', string $sort = 'article_number', string $order = 'asc', array $searchThemes = [], array $searchCommissions = [], array $searchIntervenants = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $select = 'a';

        if ($sort == 'debats_amount') {
            $select .= ', COUNT(d.id) AS HIDDEN mycount';
            $qb->leftJoin('a.debats', 'd')
                ->groupBy('a.id')
                ->orderBy('mycount', $order);
        } else if ($sort == 'citations_amount') {
            $select .= ', COUNT(l.id) AS HIDDEN mycount';
            $qb->leftJoin('a.lois', 'l')
                ->groupBy('a.id')
                ->orderBy('mycount', $order);
        } else if ($sort == 'modifications_amount') {
            $select .= ', COUNT(ar.id) AS HIDDEN mycount';
            $qb->leftJoin('a.archives', 'ar')
                ->groupBy('a.id')
                ->orderBy('mycount', $order);
        } else {
            $qb->orderBy('a.id', $order);
        }

        if ($term !== '') {
            $qb->leftJoin('a.lois', 'l2');
            $qb->andWhere('a.texte LIKE :term')->setParameter('term', '%' . $term . '%')
                ->orWhere('a.nomArticle LIKE :term')->setParameter('term', '%' . $term . '%')
                ->orWhere('l2.nomLoi LIKE :term')->setParameter('term', '%' . $term . '%')
                ->orWhere('l2.titre LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        if ($searchThemes !== []) {
            $qb->innerJoin('a.theme', 't')
                ->innerJoin('a.lois', 'l3')
                ->innerJoin('l3.theme', 't2')
                ->andWhere('t.id IN (:themes) OR t2.id IN (:themes)')
                ->setParameter('themes', $searchThemes)
                ->distinct('a.id');
        }

        if ($searchIntervenants !== []) {
            $qb->innerJoin('a.debats', 'd')
                ->innerJoin('d.intervenants', 'i')
                ->andWhere('i.id IN (:intervenants)')
                ->setParameter('intervenants', $searchIntervenants);
        }

        if ($searchCommissions !== []) {
            $qb->innerJoin('a.commission', 'c')
                ->andWhere('c.id IN (:commissions)')
                ->setParameter('commissions', $searchCommissions);
        }

        $qb->select($select);
        return $qb;
    }

    private function containsNullValue($array)
    {
        return in_array('null', $array);
    }

    public function insertCommissions()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO commission VALUES (1, 'Dispositions générales, préambule, cohésion sociale, rapports Eglises/Etat, langues', 1);
            INSERT INTO commission VALUES (2, 'Droits fondamentaux', 2);
            INSERT INTO commission VALUES (3, 'Droits politiques', 3);
            INSERT INTO commission VALUES (4, 'Tâches générales de l\'Etat, développement durable, économie, innovation', 4);
            INSERT INTO commission VALUES (5, 'Développement territorial, ressources naturelles et climat, mobilité, agriculture', 5);
            INSERT INTO commission VALUES (6, 'Tâches sociales de l\'Etat, famille, formation, intégration, culture, sports, loisirs', 6);
            INSERT INTO commission VALUES (7, 'Autorités cantonales : principes généraux et Grand Conseil', 7);
            INSERT INTO commission VALUES (8, 'Conseil d\'Etat, administration et préfets', 8);
            INSERT INTO commission VALUES (9, 'Pouvoir judiciaire', 9);
            INSERT INTO commission VALUES (10, 'Communes et organisation territoriale', 10);
        ";

        $conn->executeUpdate($sql);
    }

    public function insertThemes()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO theme VALUES (1, 'Constitution', '#c5b0d5');
            INSERT INTO theme VALUES (2, 'Etat, peuple, autorités', '#ffbb78');
            INSERT INTO theme VALUES (3, 'Droit privé, procédure civile', '#ff9896');
            INSERT INTO theme VALUES (4, 'Procédure pénale, exécution', '#d62728');
            INSERT INTO theme VALUES (5, 'Ecole, science, culture', '#2ca02c');
            INSERT INTO theme VALUES (6, 'Sécurité', '#98df8a');
            INSERT INTO theme VALUES (7, 'Finance et impôts', '#aec7e8');
            INSERT INTO theme VALUES (8, 'Travaux, énergie, transports', '#9467bd');
            INSERT INTO theme VALUES (9, 'Santé, sécurité sociale', '#ff7f0e');
            INSERT INTO theme VALUES (10, 'Economie', '#1f77b4');
        ";

        $conn->executeUpdate($sql);
    }

    public function insertArticles()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO article VALUES(1, 1, '<sup>1</sup> Le Valais est une république démocratique, souveraine dans les limites de la Constitution fédérale et incorporée comme canton à la Confédération suisse.<br/><br/><sup>2</sup> La souveraineté réside dans le peuple. Elle est exercée, directement par les électeurs et indirectement par les autorités constituées.', '1', 1, '');
            INSERT INTO article VALUES(2, 1, '<sup>1</sup> La liberté de conscience, de croyance et de libre exercice du culte sont garantis.<br/><br/><sup>2</sup> Les communautés religieuses définissent leur doctrine et aménagent leur culte en toute indépendance. Elles s\'organisent et s\'administrent d\'une manière autonome, dans les limites du droit public.<br/><br/><sup>3</sup> Le statut de personne juridique de droit public est reconnu à l\'Eglise catholique romaine et à l\'Eglise réformée évangélique. Les autres confessions sont soumises aux règles du droit privé; la loi peut leur conférer un statut de droit public pour tenir compte de leur importance sur le plan cantonal.<br/><br/><sup>4</sup> Pour autant que les paroisses de l\'Eglise catholique romaine et celles de l\'Eglise réformée évangélique ne peuvent, par leurs moyens propres, subvenir aux frais de culte des Eglises locales, ceux-ci sont, sous réserve des libertés de conscience et de croyance, mis à la charge des communes municipales. Le canton peut allouer des subventions aux Eglises reconnues de droit public.<br/><br/><sup>5</sup> La loi règle l\'application des présentes dispositions.', '2', 1, '');
            INSERT INTO article VALUES(3, 1, '<sup>1</sup> Tous les citoyens sont égaux devant la loi.<br/><br/><sup>2</sup> Il n\'y a, en Valais, aucun privilège de lieu, de naissance, de personnes ou de familles.', '3', 1, '');
            INSERT INTO article VALUES(4, 1, '<sup>1</sup> La liberté individuelle et l\'inviolabilité du domicile sont garanties.<br/><br/><sup>2</sup> Nul ne peut être poursuivi ou arrêté et aucune visite domiciliaire ne peut être faite si ce n\'est dans les cas prévus par la loi et avec les formes qu\'elle prescrit.<br/><br/><sup>3</sup> L\'Etat est tenu d\'indemniser équitablement toute personne victime d\'une erreur judiciaire ou d\'une arrestation illégale. La loi règle l\'application de ce principe.', '4', 1, '');
            INSERT INTO article VALUES(5, 1, 'Nul ne peut être distrait de son juge naturel.', '5', 1, '');
            INSERT INTO article VALUES(6, 1, '<sup>1</sup> La propriété est inviolable.<br/><br/><sup>2</sup> Il ne peut être dérogé à ce principe que pour cause d\'utilité publique, moyennant une juste indemnité et dans les formes prévues par la loi.<br/><br/><sup>3</sup> La loi peut cependant, pour cause d\'utilité publique, déterminer des cas d\'expropriation, sans indemnité, des terrains bourgeoisiaux et communaux.', '6', 1, '');
            INSERT INTO article VALUES(7, 1, 'Aucun bien-fonds ne peut être grevé d\'une redevance perpétuelle irrachetable.', '7', 1, '');
            INSERT INTO article VALUES(8, 1, 'La liberté de manifester son opinion verbalement ou par écrit, ainsi que la liberté de la presse, sont garanties. La loi en réprime les abus.', '8', 1, '');
            INSERT INTO article VALUES(9, 1, 'Le droit de pétition est garanti. La loi en règle l\'exercice.', '9', 1, '');
            INSERT INTO article VALUES(10, 1, '<sup>1</sup> Le droit de libre établissement, d\'association et de réunion, le libre exercice des professions libérales, la liberté du commerce et de l\'industrie sont garantis.<br/><br/><sup>2</sup> L\'exercice de ces droits est réglé par la loi.', '10', 1, '');
            INSERT INTO article VALUES(11, 1, '<sup>1</sup> Tout citoyen est tenu au service militaire.<br/><br/><sup>2</sup> L\'application de ce principe est réglée par la législation fédérale et cantonale.', '11', 1, '');
            INSERT INTO article VALUES(12, 1, '<sup>1</sup> La langue française et la langue allemande sont déclarées nationales.<br/><br/><sup>2</sup> L\'égalité de traitement entre les deux langues doit être observée dans la législation et dans l\'administration.', '12', 1, '');
            INSERT INTO article VALUES(13, 1, '<sup>1</sup> L\'instruction publique et l\'instruction primaire privée sont placées sous la direction et la haute surveillance de l\'Etat.<br/><br/><sup>2</sup> L\'instruction primaire est obligatoire; elle est gratuite dans les écoles publiques.<br/><br/><sup>3</sup> La liberté d\'enseignement est garantie, sous réserve des dispositions légales concernant l\'école primaire.', '13', 1, '');
            INSERT INTO article VALUES(14, 1, '<sup>1</sup> L\'Etat doit apporter à la famille, communauté de base de la société, la protection, le soutien dont elle a besoin pour que chacun de ses membres puisse s\'épanouir.<br/><br/><sup>2</sup> Il examine la législation sous l\'angle de ses effets sur les conditions de vie de la famille et l\'adapte en conséquence.', '13 bis', 1, '');
            INSERT INTO article VALUES(15, 1, 'L\'Etat édicte des prescriptions concernant la protection ouvrière et assurant la liberté du personnel', '14', 1, '');
            INSERT INTO article VALUES(16, 1, 'L\'Etat encourage et subventionne dans la mesure de ses ressources financières: a) l\'agriculture, l\'industrie, le commerce et en général toutes les branches de l\'économie publique, intéressant le canton; b) l\'enseignement professionnel concernant le commerce, l\'industrie, l\'agriculture et les arts et métiers; c) l\'élevage du bétail, l\'industrie laitière, la viticulture, l\'arboriculture, l\'économie alpestre, l\'amélioration du sol, la sylviculture et les syndicats agricoles et professionnels.', '15', 1, '');
            INSERT INTO article VALUES(17, 1, '<sup>1</sup> L\'Etat organise et subventionne l\'assurance du bétail.<br/><br/><sup>2</sup> Il peut créer d\'autres assurances et spécialement l\'assurance obligatoire mobilière et immobilière contre l\'incendie.', '16', 1, '');
            INSERT INTO article VALUES(18, 1, '<sup>1</sup> L\'Etat favorise le développement du réseau des routes et des autres moyens de communication.<br/><br/><sup>2</sup> Il contribue par des subsides au diguement du Rhône, ainsi qu\'au diguement et à la correction des rivières et des torrents.', '17', 1, '');
            INSERT INTO article VALUES(19, 1, 'L\'Etat fonde ou soutient par des subsides les établissements d\'éducation pour l\'enfance malheureuse et d\'autres institutions de bienfaisance.', '18', 1, '');
            INSERT INTO article VALUES(20, 1, '<sup>1</sup> L\'Etat doit favoriser et subventionner l\'établissement d\'hôpitaux, de cliniques et d\'infirmeries de district ou d\'arrondissement.<br/><br/><sup>2</sup> Il peut aussi créer un établissement similaire cantonal.', '19', 1, '');
            INSERT INTO article VALUES(21, 1, 'La participation financière de l\'Etat dans les cas prévus aux articles 15, 16, 17, 18 et 19 est réglée par des lois spéciales.', '20', 1, '');
            INSERT INTO article VALUES(22, 1, '<sup>1</sup> L\'Etat, les communes et les associations de communes dotées de la personnalité juridique de droit public répondent à l\'égard des tiers des actes de leurs agents.<br/><br/><sup>2</sup> L\'agent répond à l\'égard de la collectivité publique au service de laquelle il se trouve du dommage direct ou indirect qu\'il lui cause dans l\'exercice de ses fonctions, en raison d\'une faute intentionnelle ou d\'une négligence grave.<br/><br/><sup>3</sup> La loi règle l\'application de ces principes.', '21', 1, '');
            INSERT INTO article VALUES(23, 1, 'Le fonctionnaire ou l\'employé public ne peut être destitué ou révoqué qu\'après avoir été entendu ou appelé et sur décision motivée de l\'autorité qui l\'a nommé.', '22', 1, '');
            INSERT INTO article VALUES(24, 1, 'Les dépenses de l\'Etat sont couvertes: a) par les revenus de la fortune publique; b) par le produit des régales; c) par les droits du fisc et les revenus divers; d) par les indemnités, subventions et répartitions fédérales; e) par les impôts.', '23', 1, '');
            INSERT INTO article VALUES(25, 1, 'Les impôts de l\'Etat et des communes sont fixés par la loi, celle-ci consacrera le principe de la progression et l\'exemption d\'un certain minimum d\'existence.', '24', 1, '');
            INSERT INTO article VALUES(26, 1, '<sup>1</sup> Le budget de l\'Etat doit présenter un excédent de revenus et un excédent de financement assurant des investissements et participations aux investissements de tiers nécessaires au développement harmonieux du canton et permettant de garantir l\'amortissement d\'un éventuel découvert au bilan, ainsi qu\'un amortissement de la dette.<br/><br/><sup>2</sup> Si le compte s\'écarte du budget et présente un excédent de charges ou une insuffisance de financement, l\'amortissement de ces découverts doit être prévu au budget du deuxième exercice suivant.<br/><br/><sup>3</sup> Le Conseil d\'Etat propose au Grand Conseil avant la publication du projet de budget les modifications des dispositions légales ne relevant pas de sa propre compétence et qui sont nécessaires au respect de ce principe.<br/><br/><sup>4</sup> Ces modifications sont arrêtées par le Grand Conseil, par la voie du décret, dans la même session que celle où il approuve le budget.<br/><br/><sup>5</sup> La législation règle l\'application des principes posés dans cet article. Elle pourra prévoir des exceptions en fonction de la conjoncture économique ou en cas de catastrophes naturelles ou d\'autres évènements extraordinaires.', '25', 1, '');
            INSERT INTO article VALUES(27, 1, '<sup>1</sup> Le canton est divisé en districts.<br/><br/><sup>2</sup> Les districts sont composés de communes.<br/><br/><sup>4</sup> Le Grand Conseil peut, les intéressés entendus, modifier par une loi le nombre et les circonscriptions des districts et par un décret ceux des communes.<br/><br/><sup>5</sup> Il en désigne également les chefs-lieux.', '26', 1, '');
            INSERT INTO article VALUES(28, 1, '<sup>1</sup> Sion est le chef-lieu du canton et le siège du Grand Conseil, du Conseil d\'Etat et du Tribunal cantonal.<br/><br/><sup>2</sup> Ces corps peuvent toutefois siéger ailleurs si des circonstances graves l\'exigent.<br/><br/><sup>3</sup> La loi du 1er décembre 1882 détermine les prestations du chef-lieu.<br/><br/><sup>4</sup> Lors de la création d\'établissements cantonaux, on doit tenir compte des diverses parties du canton.<br/><br/><sup>5</sup> La commune qui devient le siège d\'un établissement cantonal peut être tenue à des prestations.', '27', 1, '');
            INSERT INTO article VALUES(29, 1, 'Sont Valaisans: a) les ressortissants, par droit de naissance, d\'une commune du canton; b) ceux à qui la naturalisation a été octroyée conformément à la législation cantonale.', '28', 3, '');
            INSERT INTO article VALUES(30, 1, 'Tout citoyen du canton peut acquérir le droit de cité dans d\'autres communes municipales, aux conditions fixées par la loi.', '29', 3, '');
            INSERT INTO article VALUES(31, 1, '<sup>1</sup> Outre leurs compétences en matière d\'élections, de votations et de référendum obligatoire en matière constitutionnelle, les citoyens jouissent des droits d\'initiative et de référendum facultatif.<br/><br/><sup>2</sup> La loi règle l\'exercice de ces droits ainsi que les procédures de consultation et d\'information des citoyens.', '30', 3, '');
            INSERT INTO article VALUES(32, 1, '<sup>1</sup> Trois mille citoyens actifs peuvent demander dans les nonante jours qui suivent la publication officielle que soient soumis au vote du peuple: a) les lois et les décrets; b) 	les concordats, traités et conventions renfermant des règles de droit; c) les décisions du Grand Conseil entraînant une dépense extraordinaire unique supérieure à 0.75 pour cent ou périodique supérieure à 0.25 pour cent de la dépense totale du compte de fonctionnement et du compte des investissements du dernier exercice.<br/><br/><sup>2</sup> Le référendum peut aussi être demandé par la majorité du Grand Conseil.<br/><br/><sup>3</sup> Ne sont pas soumises au vote du peuple: a) les lois d\'application (art. 42 al. 2); b) les dépenses ordinaires et les autres décisions.<br/><br/><sup>4</sup> Le Grand Conseil constate la nullité des demandes de référendum qui ne réunissent pas les conditions posées par la Constitution et par la loi.', '31', 3, '');
            INSERT INTO article VALUES(33, 1, '<sup>1</sup> Les lois, traités, concordats, conventions ou décisions soumis au référendum ne peuvent être mis en vigueur avant l\'expiration du délai de référendum, ni, le cas échéant, avant le vote du peuple.<br/><br/><sup>2</sup> Les décrets sont mis en vigueur immédiatement. Ils sont soumis au vote du peuple dans l\'année qui suit, si trois mille citoyens actifs ou la majorité du Grand Conseil le demandent. S\'ils n\'ont pas été ratifiés, ils perdent leur validité et ne peuvent être renouvelés.', '32', 3, '');
            INSERT INTO article VALUES(34, 1, '<sup>1</sup> Quatre mille citoyens actifs peuvent demander l\'élaboration, l\'adoption, la modification ou l\'abrogation d\'une loi, d\'un décret ou de toute décision susceptible de référendum, à l\'exception des lois, décrets et décisions votés par le peuple depuis moins de quatre ans, des décisions déjà exécutées et des décrets dont la validité est inférieure à un an.<br/><br/><sup>2</sup> Sauf dans les cas prévus aux articles 34 alinéa 2 et 35 alinéa 1, toute initiative populaire doit être soumise au vote du peuple dans les trois ans qui suivent son dépôt. Ce délai peut être prolongé d\'un an au plus par une décision du Grand Conseil.<br/><br/><sup>3</sup> Le Grand Conseil constate la nullité de l\'initiative qui: a) ne respecte pas le droit fédéral ou la Constitution cantonale; b) vise plus d\'une matière; c) ne respecte pas l\'unité de la forme; d) est irréalisable; e) n\'entre pas dans le domaine d\'un acte pouvant faire l\'objet d\'une initiative.<br/><br/><sup>4</sup> Lorsqu\'une demande d\'initiative doit entraîner de nouvelles dépenses ou la suppression de recettes existantes mettant en péril l\'équilibre financier, le Grand Conseil doit compléter l\'initiative en proposant de nouvelles ressources, la réduction de tâches incombant à l\'Etat ou d\'autres mesures d\'économie.', '33', 3, '');
            INSERT INTO article VALUES(35, 1, '<sup>1</sup> L\'initiative peut être rédigée de toutes pièces, sauf si elle vise une décision.<br/><br/><sup>2</sup> Si le Grand Conseil y adhère, le vote n\'a lieu qu\'à la demande de trois mille citoyens actifs ou de la majorité du Grand Conseil.<br/><br/><sup>3</sup> Si le Grand Conseil n\'y adhère pas, il doit soumettre l\'initiative telle quelle au vote du peuple, mais il peut en recommander le rejet ou également lui opposer un contre-projet.<br/><br/><sup>4</sup> Lorsque le Grand Conseil adopte un contre-projet, les citoyens sont invités à répondre, sur le même bulletin de vote, aux trois questions suivantes: a) Acceptez-vous l\'initiative populaire? b) Acceptez-vous le contre-projet? c) 	Au cas où les deux textes obtiennent la majorité absolue des électeurs ayant voté valablement, est-ce l\'initiative ou le contre-projet qui doit entrer en vigueur?', '34', 3, '');
            INSERT INTO article VALUES(36, 1, '<sup>1</sup> L\'initiative conçue en termes généraux est réalisée par le Grand Conseil, qui décide si les dispositions qu\'il adopte ou modifie figureront dans la Constitution ou dans un acte législatif ou administratif; lorsque l\'initiative est réalisée dans un acte législatif ou administratif, elle n\'est soumise au vote que si trois mille citoyens actifs ou la majorité du Grand Conseil le demandent.<br/><br/><sup>2</sup> Lorsque le Grand Conseil n\'approuve pas l\'initiative, il la soumet telle quelle au vote du peuple, avec son préavis.<br/><br/><sup>3</sup> Si le peuple la rejette, elle est classée.<br/><br/><sup>4</sup> Si le peuple l\'accepte, le Grand Conseil est tenu d\'y donner suite sans retard.<br/><br/><sup>5</sup> En rédigeant les règles demandées par l\'initiative non formulée, le Grand Conseil respecte les intentions de ses auteurs.', '35', 3, '');
            INSERT INTO article VALUES(37, 1, 'Les pouvoirs publics sont: a) le pouvoir législatif; b) le pouvoir exécutif et administratif; c) le pouvoir judiciaire', '36', 7, '');
            INSERT INTO article VALUES(38, 1, '<sup>1</sup> Le Grand Conseil exerce le pouvoir législatif, sous réserve des droits du peuple.<br/><br/><sup>2</sup> Il jouit de toute autre compétence qui lui est attribuée par la Constitution ou la loi.', '37', 7, '');
            INSERT INTO article VALUES(39, 1, '<sup>1</sup> Le Grand Conseil élabore les dispositions constitutionnelles, les lois et les décrets, les articles 31 à 35 et 100 à 106 étant réservés.<br/><br/><sup>2</sup> Il approuve les traités, les concordats et les conventions, sous réserve des compétences du peuple et du Conseil d\'Etat.<br/><br/><sup>3</sup> Il exerce les droits réservés aux cantons par les articles 86, 89, 89bis et 93 de la Constitution fédérale et répond aux consultations de la Confédération en matière d\'installations atomiques.', '38', 7, '');
            INSERT INTO article VALUES(40, 1, '<sup>1</sup> Le Grand Conseil statue sur la validité des élections de ses membres.<br/><br/><sup>2</sup> Il élit le Tribunal cantonal, son président et son vice-président ainsi que les membres du Bureau du Ministère public. ', '39', 7, '');
            INSERT INTO article VALUES(41, 1, '<sup>1</sup> Le Grand Conseil exerce la haute surveillance sur la gestion du Conseil d\'Etat, des corporations et établissements autonomes de droit public, des autorités judiciaires, ainsi que sur les représentants de l\'Etat dans les sociétés où le canton a une participation prépondérante. Il examine la gestion et délibère sur son approbation.<br/><br/><sup>2</sup> Il peut en tout temps demander compte au pouvoir exécutif d\'un acte de son administration.<br/><br/><sup>3</sup> La loi peut confier certaines tâches de l\'Etat à des corporations ou établissements autonomes de droit public.', '40', 7, '');
            INSERT INTO article VALUES(42, 1, 'Le Grand Conseil a notamment les attributions suivantes: a) il arrête le budget et approuve les comptes, qui sont rendus publics; b) il participe à la planification dans la mesure fixée par la loi; c) il décide les dépenses et autorise les concessions, les transactions immobilières, les emprunts et l\'octroi des cautionnements et autres garanties analogues, sauf exceptions prévues par la Constitution ou par la loi; d) il fixe le traitement des magistrats, fonctionnaires et employés de l\'Etat, sauf exceptions prévues par la loi; e) il exerce le droit de grâce.', '41', 7, '');
            INSERT INTO article VALUES(43, 1, '<sup>1</sup> Le Grand Conseil édicte les règles de droit sous la forme de loi, qui est, en principe, mise en vigueur pour une durée illimitée. Il peut toutefois prévoir que la loi est mise en vigueur pour un temps limité.<br/><br/><sup>2</sup> Il édicte, sous forme de loi d\'application, les dispositions absolument nécessaires pour assurer l\'exécution du droit de rang supérieur.<br/><br/><sup>3</sup> Il peut toutefois prendre des dispositions urgentes par la voie du décret, pour un temps limité, lorsque les circonstances l\'exigent (art. 32 al. 2).<br/><br/><sup>4</sup> Le Grand Conseil traite toutes les autres affaires sous forme de décision.', '42', 7, '');
            INSERT INTO article VALUES(44, 1, '<sup>1</sup> La loi fixe les grandes lignes de l\'organisation du Grand Conseil ainsi que de ses rapports avec le Conseil d\'Etat et les autorités judiciaires. Pour le surplus, le Grand Conseil s\'organise lui-même. <br/><br/><sup>2</sup> Elle règle la participation des membres du Conseil d\'Etat aux séances de l\'assemblée et des commissions parlementaires.', '43', 7, '');
            INSERT INTO article VALUES(45, 1, '<sup>1</sup> Le Grand Conseil s\'assemble de plein droit: a) en session constitutive le quatrième lundi qui suit son renouvellement intégral; b) en sessions ordinaires, aux échéances fixées par la loi.<br/><br/><sup>2</sup> Le Grand Conseil s\'assemble en sessions extraordinaires: a) lorsqu\'il le décide spécialement; b) sur l\'invitation du Conseil d\'Etat; c) quand vingt députés le demandent en indiquant les objets à traiter.', '44', 7, '');
            INSERT INTO article VALUES(46, 1, '<sup>1</sup> Le Grand Conseil élit pour un an un président et deux vice-présidents.<br/><br/><sup>2</sup> Le Grand Conseil dispose d\'un service parlementaire indépendant.', '45', 7, '');
            INSERT INTO article VALUES(47, 1, '<sup>1</sup> Le Grand Conseil désigne des commissions, permanentes ou non, qui préparent ses délibérations. Cette compétence peut être déléguée au bureau.<br/><br/><sup>2</sup> Les députés peuvent former des groupes politiques, qui doivent avoir au moins cinq membres.<br/><br/><sup>3</sup> En principe, les groupes politiques doivent être représentés de manière équitable dans les commissions.', '46', 7, '');
            INSERT INTO article VALUES(48, 1, '<sup>1</sup> Le Grand Conseil ne peut délibérer que si la majorité absolue de ses membres sont présents.<br/><br/><sup>2</sup> Il prend ses décisions à la majorité absolue.', '47', 7, '');
            INSERT INTO article VALUES(49, 1, '<sup>1</sup> Les séances du Grand Conseil sont publiques.<br/><br/><sup>2</sup> Il peut toutefois décider le huis clos lorsque les circonstances l\'exigent.', '48', 7, '');
            INSERT INTO article VALUES(50, 1, '<sup>1</sup> Les projets de loi et de décret font l\'objet de deux lectures.<br/><br/><sup>2</sup> Les décisions font l\'objet d\'une seule lecture.<br/><br/><sup>3</sup> Le Grand Conseil peut dans tous les cas décider d\'une seule lecture ou d\'une lecture supplémentaire.', '49', 7, '');
            INSERT INTO article VALUES(51, 1, '<sup>1</sup> Les députés remplissent librement leur mandat.<br/><br/><sup>2</sup> Ils ne peuvent être poursuivis pénalement sans autorisation de l\'assemblée pour les propos qu\'ils tiennent devant elle ou en commission.<br/><br/><sup>3</sup> Sauf en cas de flagrant délit, ils ne peuvent être arrêtés pendant les sessions sans autorisation de l\'assemblée.', '50', 7, '');
            INSERT INTO article VALUES(52, 1, '<sup>1</sup> Les droits d\'initiative, de motion, de postulat, d\'interpellation, de résolution et de question écrite appartiennent à chaque membre du Grand Conseil.<br/><br/><sup>2</sup> La loi définit ces droits et en règle l\'exercice.', '51', 7, '');
            INSERT INTO article VALUES(53, 1, '<sup>1</sup> Le pouvoir exécutif et administratif est confié à un Conseil d\'Etat composé de cinq membres.<br/><br/><sup>2</sup> Un d\'entre eux est choisi parmi les électeurs des districts actuels de Conches, Brigue, Viège, Rarogne et Loèche; un parmi les électeurs des districts de Sierre, Sion, Hérens et Conthey et un parmi les électeurs des districts de Martigny, Entremont, Saint-Maurice et Monthey. <br/><br/><sup>3</sup> Les deux autres sont choisis sur l\'ensemble de tous les électeurs du canton. Toutefois, il ne pourra y avoir plus d\'un conseiller d\'Etat nommé parmi les électeurs d\'un même district.<br/><br/><sup>4</sup> Les membres du Conseil d\'Etat sont élus directement par le peuple, le même jour que les députés au Grand Conseil, pour entrer en fonction le premier mai suivant. Leur élection a lieu avec le système majoritaire. Le Conseil d\'Etat se constitue lui-même chaque année; le président sortant de charge n\'est pas immédiatement rééligible.<br/><br/><sup>5</sup> Il est pourvu à toute vacance au Conseil d\'Etat dans les soixante jours, à moins que le renouvellement intégral n\'intervienne dans les quatre mois.<br/><br/><sup>6</sup> La nomination des membres du Conseil d\'Etat a lieu par un même scrutin de liste. Si les nominations ne sont pas terminées au jour fixé pour les élections, elles seront reprises le deuxième dimanche qui suit. Dans ce cas, le résultat de la première opération et l\'avis de la reprise des opérations seront publiés immédiatement.<br/><br/><sup>7</sup> Si tous les membres à élire ne réunissent pas la majorité au premier tour de scrutin, il est procédé à un second tour. Sont élus au second tour, ceux qui ont réuni le plus grand nombre de voix, alors même qu\'ils n\'auraient pas obtenu la majorité absolue. Toutefois, si, au deuxième tour, le nombre de sièges à repourvoir correspond au nombre de candidats proposés, ceux-ci sont proclamés élus, sans scrutin. L\'élection tacite s\'applique également au premier tour des scrutins de remplacement lorsqu\'il n\'y a qu\'un seul candidat et un seul poste à repourvoir. <br/><br/><sup>8</sup> Si le nombre des citoyens qui ont obtenu la majorité absolue dépasse celui des citoyens à élire, ceux qui ont obtenu le plus grand nombre de voix sont nommés.<br/><br/><sup>9</sup> Au cas où deux ou plusieurs citoyens du même district auraient obtenu la majorité absolue, celui qui aura obtenu le plus grand nombre de voix sera seul nommé.<br/><br/><sup>10</sup> En cas d\'égalité de suffrages, le sort décide.', '52', 8, '');
            INSERT INTO article VALUES(54, 1, '<sup>1</sup> Le Conseil d\'Etat exerce le pouvoir exécutif et administratif et jouit de toute compétence qui lui est attribuée par la Constitution ou par la loi.<br/><br/><sup>2</sup> Il agit en collège.<br/><br/><sup>3</sup> Les affaires importantes restent toujours de sa compétence.<br/><br/><sup>4</sup> Il répartit les affaires entre les départements, dont le nombre et les attributions sont fixés par une ordonnance approuvée par le Grand Conseil.<br/><br/><sup>5</sup> Pour le surplus, le Conseil d\'Etat s\'organise lui-même.', '53', 8, '');
            INSERT INTO article VALUES(55, 1, 'Dans ses relations avec le Grand Conseil, le Conseil d\'Etat a notamment les attributions suivantes: a) il présente les projets de dispositions constitutionnelles, de lois, de décrets ou de décisions; b) il fait rapport sur les initiatives populaires, sur les initiatives, motions, postulats et résolutions des députés, et répond à leurs interpellations et questions; c) il soumet au Grand Conseil le projet de budget, les comptes de l\'Etat et le rapport de gestion; d) il peut faire des propositions au Grand Conseil; e) il soumet au Grand Conseil les projets de traités, conventions et concordats qui renferment des règles de droit ou engendrent des dépenses relevant de sa compétence.', '54', 8, '');
            INSERT INTO article VALUES(56, 1, 'Le Conseil d\'Etat exerce notamment les compétences administratives suivantes: a) il nomme le personnel de l\'Etat, sauf exceptions prévues par la loi; b) il surveille les autorités inférieures ainsi que les corporations et établissements de droit public; c) il représente l\'Etat, conclut les traités, concordats et conventions de droit public, et répond aux consultations requises du canton; d) il dirige l\'administration, planifie et coordonne ses activités.', '55', 8, '');
            INSERT INTO article VALUES(57, 1, '<sup>1</sup> Le Conseil d\'Etat assure l\'ordre public et dispose à cette fin des forces policières et militaires du canton.<br/><br/><sup>2</sup> Il exerce les pouvoirs extraordinaires en cas de danger grave et imminent, en avisant immédiatement le Grand Conseil des mesures qu\'il prend.', '56', 8, '');
            INSERT INTO article VALUES(58, 1, '<sup>1</sup> Le Conseil d\'Etat édicte sous forme de règlement les dispositions nécessaires à l\'application des lois et décrets cantonaux.<br/><br/><sup>2</sup> La loi peut déléguer au Conseil d\'Etat la compétence d\'édicter des ordonnances en fixant leur but et les principes qui régissent leur contenu. La délégation doit toucher un domaine déterminé. Les ordonnances peuvent être subordonnées à l\'approbation du Grand Conseil.<br/><br/><sup>3</sup> Le Conseil d\'Etat traite les autres affaires sous forme d\'arrêté ou de décision.', '57', 8, '');
            INSERT INTO article VALUES(59, 1, '<sup>1</sup> Le Conseil d\'Etat promulgue les règles de droit, les met en vigueur, à moins que le Grand Conseil ne le décide lui-même et pourvoit à leur application.<br/><br/><sup>2</sup> Il met en vigueur les dispositions constitutionnelles directement applicables immédiatement après leur approbation par l\'Assemblée fédérale.', '58', 8, '');
            INSERT INTO article VALUES(60, 1, '<sup>1</sup> Le Gouvernement a, dans chaque district, un représentant sous le nom de préfet et un sous-préfet.<br/><br/><sup>2</sup> Les attributions du préfet sont déterminées par la loi.', '59', 8, '');
            INSERT INTO article VALUES(61, 1, 'Le pouvoir judiciaire est indépendant.', '60', 9, '');
            INSERT INTO article VALUES(62, 1, 'Le Tribunal cantonal présente annuellement au Grand Conseil, par l\'intermédiaire du Conseil d\'Etat, un rapport sur toutes les parties de l\'administration judiciaire.', '61', 9, '');
            INSERT INTO article VALUES(63, 1, '<sup>1</sup> Il y a par commune ou par cercle un juge et un juge substitut; par arrondissement, un tribunal au civil, au correctionnel et au criminel; et pour le canton, un Tribunal cantonal.<br/><br/><sup>2</sup> Les membres du Tribunal cantonal doivent connaître les deux langues nationales.', '62', 9, '');
            INSERT INTO article VALUES(64, 1, '<sup>1</sup> Le nombre des arrondissements, la composition et la compétence des tribunaux, la nomination et le mode de rétribution des juges, ainsi que l\'incompatibilité entre les fonctions judiciaires et d\'autres fonctions sont déterminées par la loi.<br/><br/><sup>2</sup> Il ne peut y avoir plus de quatre tribunaux d\'arrondissement.<br/><br/><sup>3</sup> Les juges de cercle ou de communes et leurs substituts sont nommés par les électeurs du cercle ou de la commune.<br/><br/><sup>4</sup> Pour la formation des cercles, on tient compte de la population des communes et de leur situation topographique.<br/><br/><sup>5</sup> Le vote a lieu dans chaque commune.', '63', 9, '');
            INSERT INTO article VALUES(65, 1, 'Il peut être institué, par voie législative, un tribunal de commerce et un ou plusieurs tribunaux de prud\'hommes.', '64', 9, '');
            INSERT INTO article VALUES(66, 1, '<sup>1</sup> Il y a un tribunal du contentieux de l\'administration et une cour chargée de statuer sur les conflits de compétence entre le pouvoir administratif et le pouvoir judiciaire.<br/><br/><sup>2</sup> Cette cour et ce tribunal sont organisés par des lois spéciales.', '65', 9, '');
            INSERT INTO article VALUES(67, 1, '<sup>1</sup> Le Conseil de la magistrature est une autorité indépendante de surveillance de la Justice.<br/><br/><sup>2</sup> Il exerce la surveillance administrative et disciplinaire sur les autorités judiciaires cantonales et les magistrats du ministère public. Est réservée la compétence exclusive du Grand Conseil de révoquer, pour de justes motifs, les magistrats qu\'il a élus.<br/><br/><sup>3</sup> Il est soumis à la haute surveillance du Grand Conseil.<br/><br/><sup>4</sup> Le Grand Conseil élit les membres du Conseil de la magistrature qui ne sont pas désignés par la loi.<br/><br/><sup>5</sup> Pour le surplus, la loi fixe: a) la composition, le mode de désignation et l\'organisation du Conseil de la magistrature; b) la voie de recours contre les décisions du Conseil de la magistrature; c) les rapports du Conseil de la magistrature avec le Grand Conseil, le Tribunal cantonal et le Ministère public; d) la collaboration du Conseil de la magistrature aux élections judiciaires.', '65 bis', 9, '');
            INSERT INTO article VALUES(68, 1, '<sup>1</sup> Il y a dans chaque district un conseil de district nommé pour quatre ans.<br/><br/><sup>2</sup> Le conseil de la commune nomme ses délégués au conseil de district, à raison d\'un délégué sur 300 âmes de population.<br/><br/><sup>3</sup> La fraction de 151 compte pour l\'entier.<br/><br/><sup>4</sup> Chaque commune a un délégué, quelle que soit sa population.', '66', 10, '');
            INSERT INTO article VALUES(69, 1, '<sup>1</sup> Le conseil de district est présidé par le préfet du district ou son substitut.<br/><br/><sup>2</sup> Il prend annuellement connaissance du compte rendu de l\'administration financière de l\'Etat.<br/><br/><sup>3</sup> Il représente le district et veille spécialement à son développement économique et à l\'écoulement de ses produits agricoles.', '67', 10, '');
            INSERT INTO article VALUES(70, 1, 'La loi détermine l\'organisation et les autres attributions de ce conseil.', '68', 10, '');
            INSERT INTO article VALUES(71, 1, 'Les communes sont autonomes dans le cadre de la constitution et des lois. Elles sont compétentes pour accomplir les tâches locales et celles qu\'elles peuvent assumer seules ou en s\'associant avec d\'autres communes.', '69', 10, '');
            INSERT INTO article VALUES(72, 1, '<sup>1</sup> Les communes jouissent de leur autonomie en respectant le bien commun et l\'intérêt des autres collectivités publiques.<br/><br/><sup>2</sup> Elles accomplissent leurs tâches propres et celles que leur attribue la loi.<br/><br/><sup>3</sup> Elles utilisent judicieusement et administrent avec soin le patrimoine communal.', '70', 10, '');
            INSERT INTO article VALUES(73, 1, '<sup>1</sup> Les communes peuvent s\'associer pour réaliser en commun certaines tâches d\'utilité publique et constituer à cet effet des associations de droit public dotées de la personnalité juridique ou collaborer de toute autre manière. La loi fixe les principes de la collaboration, de la création et du fonctionnement des associations de communes.<br/><br/><sup>2</sup> Sous certaines conditions précisées par la loi, le Conseil d\'Etat peut contraindre des communes à collaborer ou à s\'associer.', '71', 10, '');
            INSERT INTO article VALUES(74, 1, '<sup>1</sup> Il y a dans chaque commune: a) une assemblée des citoyens habiles à voter dans la commune; b) un conseil communal élu par l\'assemblée des citoyens.<br/><br/><sup>2</sup> L\'assemblée des citoyens choisit un président et un vice-président parmi les conseillers.<br/><br/><sup>3</sup> Pour le surplus, la loi fixe les principes de l\'organisation des communes.', '72', 10, '');
            INSERT INTO article VALUES(75, 1, '<sup>1</sup> Dans les communes de plus de 700 habitants, l\'assemblée des citoyens peut élire un conseil général. La loi détermine l\'organisation et les compétences.<br/><br/><sup>2</sup> Les citoyens ont un droit de référendum facultatif contre les décisions prises par le conseil général à la place de l\'assemblée communale. La loi règle l\'exercice de ce droit.<br/><br/><sup>3</sup> Ces dispositions ne sont pas applicables à la commune bourgeoisiale.', '73', 10, '');
            INSERT INTO article VALUES(76, 1, '<sup>1</sup> Les communes ont la faculté d\'introduire le droit d\'initiative. Dans les communes connaissant ce droit, les citoyens peuvent adresser au conseil communal des initiatives conçues en termes généraux, portant sur l\'adoption ou la modification de règlements qui sont de la compétence de l\'assemblée communale.<br/><br/><sup>2</sup> La loi règle les modalités d\'introduction et d\'exercice de ce droit.', '74', 10, '');
            INSERT INTO article VALUES(77, 1, '<sup>1</sup> Les communes sont soumises à la surveillance du Conseil d\'Etat dans les limites de l\'article 69. La loi détermine la nature de cette surveillance, notamment en matière de gestion. Dans la mesure où la constitution et les lois ne prévoient pas expressément le contraire, le pouvoir d\'examen du Conseil d\'Etat se restreint à la légalité.<br/><br/><sup>2</sup> Les règlements élaborés par les communes doivent être homologués par le Conseil d\'Etat.<br/><br/><sup>3</sup> La loi peut prévoir que des projets importants des communes soient soumis à l\'homologation ou à l\'approbation du Conseil d\'Etat. <br/><br/><sup>4</sup> La loi fixe les modalités de l\'homologation.', '75', 10, '');
            INSERT INTO article VALUES(78, 1, 'Sont considérées comme communes: a) les communes municipales; b) les communes bourgeoisiales.', '76', 10, '');
            INSERT INTO article VALUES(79, 1, '<sup>1</sup> La commune municipale est composée des personnes habitant le territoire communal.<br/><br/><sup>2</sup> Sous réserve de l\'article 26, le territoire des communes municipales est garanti.', '77', 10, '');
            INSERT INTO article VALUES(80, 1, '<sup>1</sup> L\'assemblée primaire est composée des citoyens habiles à voter dans la commune.<br/><br/><sup>2</sup> Elle élit un conseil municipal de trois à quinze membres, le président ainsi que le vice-président et, le cas échéant, le conseil général.<br/><br/><sup>3</sup> Dans les communes sans conseil général, l\'assemblée primaire décide notamment: a) des règlements communaux, sauf exceptions fixées par la loi; b) des projets importants de vente, d\'octroi de droits réels restreints, d\'échange, de bail, d\'aliénation de capitaux, de prêt, d\'emprunt, de cautionnement, d\'octroi et de transfert de concessions hydrauliques; c) des dépenses nouvelles de caractère non obligatoire dont le montant est fixé par la loi; d) du budget et des comptes.<br/><br/><sup>4</sup> Dans les autres communes, le conseil général remplace l\'assemblée primaire dont il a au moins les mêmes compétences, sauf en matière électorale.<br/><br/><sup>5</sup> Dans les deux cas la loi fixe les autres compétences et règle l\'exercice de ces droits.', '78', 10, '');
            INSERT INTO article VALUES(81, 1, '<sup>1</sup> Le conseil municipal a les attributions suivantes: a) il pourvoit à l\'administration communale; b) il élabore et applique les règlements communaux; c) il fait exécuter la législation cantonale; d) il nomme les employés; e) il élabore le budget; f) il établit les comptes.<br/><br/><sup>2</sup> Dans les communes sans conseil bourgeoisial, le conseil municipal en remplit les fonctions.', '79', 10, '');
            INSERT INTO article VALUES(82, 1, 'La commune bourgeoisiale est une collectivité de droit public chargée de réaliser des tâches d\'intérêt public fixées par la loi.', '80', 10, '');
            INSERT INTO article VALUES(83, 1, '<sup>1</sup> L\'assemblée bourgeoisiale est composée des bourgeois domiciliés sur le territoire bourgeoisial. La loi peut étendre l\'exercice de certains droits aux bourgeois domiciliés dans le canton.<br/><br/><sup>2</sup> L\'assemblée bourgeoisiale a, sur le plan bourgeoisial, les mêmes compétences que l\'assemblée primaire. Elle décide en outre de la réception des nouveaux bourgeois.', '81', 10, '');
            INSERT INTO article VALUES(84, 1, '<sup>1</sup> L\'assemblée bourgeoisiale a le droit de demander la formation d\'un conseil bourgeoisial séparé. Cette demande doit être présentée à la fin d\'une période administrative, selon les prescriptions de la loi.<br/><br/><sup>2</sup> Le conseil bourgeoisial se compose de trois membres au moins et de neuf au plus.', '82', 10, '');
            INSERT INTO article VALUES(85, 1, NULL, '83', 10, '');
            INSERT INTO article VALUES(86, 1, '<sup>1</sup> Le Grand Conseil se compose de 130 députés et d\'autant de suppléants répartis entre les districts et élus directement par le peuple.<br/><br/><sup>2</sup> Le district de Rarogne, composé de deux demi-districts disposant chacun de ses propres organes et compétences, forme deux arrondissements électoraux.<br/><br/><sup>3</sup> Le mode de répartition des sièges entre les districts et demi-districts est le suivant: Le chiffre total de la population suisse de résidence est divisé par 130. Le quotient ainsi obtenu est élevé au nombre entier immédiatement supérieur et celui-ci constitue le quotient électoral. Chaque district ou demi-district obtient autant de députés et de suppléants que le chiffre de sa population suisse de résidence contient de fois le quotient électoral. Si après cette répartition tous les sièges ne sont pas encore attribués, les sièges restants sont dévolus aux districts et aux demi-districts qui accusent les plus forts restes.<br/><br/><sup>4</sup> Le Conseil d\'Etat fixe après chaque recensement de la population le nombre de sièges attribués à chaque district et demi-district.<br/><br/><sup>5</sup> La votation du peuple a lieu dans les communes.<br/><br/><sup>6</sup> L\'élection se fait par district et demi-district, d\'après le système de la représentation proportionnelle. Le mode d\'application de ce principe est déterminé par la loi.', '84', 7, '');
            INSERT INTO article VALUES(87, 1, '<sup>1</sup> Le Grand Conseil, le Conseil d\'Etat, les fonctionnaires de l\'ordre judiciaire, les conseils communaux et les conseils bourgeoisiaux sont nommés pour une période de quatre ans.<br/><br/><sup>2</sup> Le président et le vice-président du Conseil d\'Etat sont soumis à la réélection toutes les années. Le président n\'est pas immédiatement rééligible.', '85', 8, '');
            INSERT INTO article VALUES(88, 1, '<sup>1</sup> Les députés au Conseil des Etats sont nommés directement par le peuple lors des élections pour le renouvellement ordinaire du Conseil national. Ces élections se font avec le système majoritaire dans tout le canton formant un seul arrondissement électoral. <br/><br/><sup>2</sup> La nomination des députés au Conseil des Etats a lieu par un même scrutin de liste. Si les nominations ne sont pas terminées au jour fixé pour les élections, elles seront reprises le deuxième dimanche qui suit. Dans ce cas, le résultat de la première opération et l\'avis de reprise des opérations seront publiés immédiatement.<br/><br/><sup>3</sup> Si tous les députés ne réunissent pas la majorité absolue au premier tour de scrutin, il est procédé à un second tour. Sont élus au second tour ceux qui ont réuni le plus grand nombre de voix, alors même qu\'ils n\'auraient pas obtenu la majorité absolue. Toutefois, si, au deuxième tour, le nombre de députés à élire correspond au nombre de candidats proposés, ceux-ci sont proclamés élus, sans scrutin. L\'élection tacite s\'applique également au premier tour des scrutins de remplacement lorsqu\'il n\'y a qu\'un seul candidat et un seul poste à repourvoir.<br/><br/><sup>4</sup> Si le nombre des citoyens qui ont obtenu la majorité absolue dépasse celui des citoyens à élire, ceux qui ont obtenu le plus grand nombre de voix sont nommés.<br/><br/><sup>5</sup> Si le nombre des citoyens qui ont obtenu la majorité absolue dépasse celui des citoyens à élire, ceux qui ont obtenu le plus grand nombre de voix sont nommés.', '85 bis', 3, '');
            INSERT INTO article VALUES(89, 1, '<sup>1</sup> La nomination des membres et des suppléants du Grand Conseil a lieu le premier dimanche de mars, pour chaque renouvellement de législature.<br/><br/><sup>2</sup> Le Grand Conseil nouvellement élu entre en fonctions à l\'ouverture de la session constitutive.', '86', 7, '');
            INSERT INTO article VALUES(90, 1, '<sup>1</sup> Les membres du conseil général sont élus par le corps électoral selon le système proportionnel.<br/><br/><sup>2</sup> Les membres du conseil municipal et bourgeoisial sont élus par le corps électoral selon le système proportionnel. Dans les communes bourgeoisiales et dans les communes municipales dont la population est inférieure au nombre fixé dans la loi, le corps électoral peut, à la majorité de ses membres, décider un changement du système d\'élection aux conditions fixées par la loi. Le système majoritaire est maintenu dans les communes bourgeoisiales et dans les communes municipales qui connaissent ce système à l\'entrée en vigueur de la présente réforme.<br/><br/><sup>3</sup> Le président, le vice-président, le juge et le vice-juge sont élus par le corps électoral selon le système majoritaire.<br/><br/><sup>4</sup> La loi fixe les modalités d\'élection et la date du scrutin.', '87', 10, '');
            INSERT INTO article VALUES(91, 1, '<sup>1</sup> Les citoyens et citoyennes exercent leurs droits politiques à l\'âge de 18 ans révolus.<br/><br/><sup>2</sup> Tout électeur et toute électrice est éligible aux fonctions publiques.', '88', 3, '');
            INSERT INTO article VALUES(92, 1, 'Le citoyen ne peut voter que dans une seule commune municipale et bourgeoisiale.', '89', 3, '');
            INSERT INTO article VALUES(93, 1, '<sup>1</sup> La loi règle les incompatibilités.<br/><br/><sup>2</sup> Elle veille notamment à éviter que: a) le même citoyen occupe simultanément des fonctions qui relèvent de plusieurs pouvoirs publics; b) la même personne appartienne à deux organes dont l\'un est subordonné à l\'autre; c) les membres de la même famille siègent dans la même autorité; d) le citoyen investi d\'une fonction publique exerce d\'autres activités qui porteraient préjudice à l\'accomplissement de sa fonction.<br/><br/><sup>3</sup> Sauf exception prévue par la loi, les incompatibilités sont applicables aux suppléants et aux substituts.<br/><br/><sup>4</sup> La loi peut prévoir d\'autres exceptions, notamment pour le régime communal.<br/><br/><sup>5</sup> Un seul membre du Conseil d\'Etat peut siéger aux Chambres fédérales.', '90', 7, '');
            INSERT INTO article VALUES(94, 1, NULL, '91', NULL, '');
            INSERT INTO article VALUES(95, 1, 'Les cas d\'exclusion du droit de vote et du droit d\'éligibilité sont déterminés par la législation fédérale et cantonale.', '92', 3, '');
            INSERT INTO article VALUES(96, 1, NULL, '93', NULL, '');
            INSERT INTO article VALUES(97, 1, NULL, '94', NULL, '');
            INSERT INTO article VALUES(98, 1, NULL, '95', NULL, '');
            INSERT INTO article VALUES(99, 1, NULL, '96', NULL, '');
            INSERT INTO article VALUES(100, 1, NULL, '97', NULL, '');
            INSERT INTO article VALUES(101, 1, NULL, '98', NULL, '');
            INSERT INTO article VALUES(102, 1, NULL, '99', NULL, '');
            INSERT INTO article VALUES(103, 1, '<sup>1</sup> Six mille citoyens actifs peuvent demander la révision totale ou partielle de la Constitution.<br/><br/><sup>2</sup> Toute initiative populaire doit être soumise au vote du peuple dans les trois ans qui suivent son dépôt. Ce délai peut être prolongé d\'un an au plus par une décision du Grand Conseil.<br/><br/><sup>3</sup> Le Grand Conseil constate la nullité de l\'initiative qui: a) est contraire au droit fédéral; b) vise plus d\'une matière; c) ne respecte pas l\'unité de la forme; d) n\'entre pas dans le domaine de la Constitution; e) est irréalisable.', '100', 1, '');
            INSERT INTO article VALUES(104, 1, '<sup>1</sup> L\'initiative conçue en termes généraux est soumise au vote du peuple, avec un préavis du Grand Conseil.<br/><br/><sup>2</sup> Si le peuple la rejette, elle est classée.<br/><br/><sup>3</sup> Si le peuple l\'accepte, le Grand Conseil est tenu d\'y donner suite sans retard.<br/><br/><sup>4</sup> En rédigeant les règles demandées par l\'initiative non formulée, le Grand Conseil respecte les intentions de ses auteurs.<br/><br/><sup>5</sup> Le peuple décide en même temps si, en cas de vote affirmatif, la révision totale doit être faite par le Grand Conseil ou par une constituante.', '101', 1, '');
            INSERT INTO article VALUES(105, 1, '<sup>1</sup> La révision partielle de la Constitution peut être demandée sous la forme d\'un projet rédigé de toutes pièces.<br/><br/><sup>2</sup> Le Grand Conseil peut recommander le rejet ou l\'acceptation ou également lui opposer un contre-projet.<br/><br/><sup>3</sup> Lorsqu\'il élabore un contre-projet, il en délibère en deux sessions ordinaires. Le Grand Conseil peut décider une lecture supplémentaire.<br/><br/><sup>4</sup> Lorsque le Grand Conseil adopte un contre-projet, les citoyens sont invités à répondre, sur le même bulletin de vote, aux trois questions suivantes: a)Acceptez-vous l\'initiative populaire?  b) Acceptez-vous le contre-projet?  c) Au cas où les deux textes obtiennent la majorité absolue des votants, est-ce l\'initiative ou le contre-projet qui doit entrer en vigueur?', '102', 1, '');
            INSERT INTO article VALUES(106, 1, '<sup>1</sup> Si, par suite du vote populaire, la révision doit se faire par le Grand Conseil, elle est discutée en deux sessions ordinaires.<br/><br/><sup>2</sup> Si elle se fait par une constituante, elle est discutée en deux débats.<br/><br/><sup>3</sup> Les élections à la constituante se font sur la même base que les élections au Grand Conseil. Aucune des incompatibilités prévues par ces dernières ne leur est applicable.', '103', 1, '');
            INSERT INTO article VALUES(107, 1, '<sup>1</sup> Le Grand Conseil peut aussi, de sa propre initiative, réviser la Constitution.<br/><br/><sup>2</sup> Les révisions font d\'abord l\'objet d\'un débat sur l\'opportunité, puis de deux débats sur le texte, dans des sessions ordinaires.<br/><br/><sup>3</sup> Dans tous les cas, le Grand Conseil peut décider une lecture supplémentaire. Il peut également demander au peuple de se prononcer sur des variantes.', '104', 1, '');
            INSERT INTO article VALUES(108, 1, 'Le Constitution révisée par le Grand Conseil ou par une constituante est soumise à la votation du peuple.', '105', 1, '');
            INSERT INTO article VALUES(109, 1, 'La majorité absolue des citoyens ayant pris part au vote décide dans les votations ordonnées en exécution des articles 102 et 105.', '106', 1, '');
            INSERT INTO article VALUES(110, 1, '<sup>1</sup> Toute demande de révision émanant de l\'initiative populaire doit être adressée au Grand Conseil.<br/><br/><sup>2</sup> Les signatures qui appuient la demande sont données par commune et la capacité électorale des signataires doit être attestée par le président de la commune. Celle-ci doit également s\'assurer de l\'authenticité des signatures qui lui paraîtraient suspectes.', '107', 1, '');
            INSERT INTO article VALUES(111, 1, '<sup>1</sup> Les actes adoptés par le Grand Conseil avant la date de la mise en vigueur des nouvelles dispositions constitutionnelles sont soumis au référendum obligatoire, conformément à l\'ancien article 30 de la Constitution cantonale.<br/><br/><sup>2</sup> Les initiatives populaires déposées à la Chancellerie avant cette date sont soumises aux anciens articles 31 à 35 ou aux anciens articles 101 à 107 de la Constitution cantonale.<br/><br/><sup>3</sup> Le Grand Conseil est habilité à modifier l\'ordre et la numérotation des anciens articles 49, 50, 55, 56 et 57 de la Constitution si le nouvel article 90 régissant les incompatibilités n\'est pas agréé par le peuple.', '108', 1, '');
            INSERT INTO article VALUES(112, 1, 'Les anciens articles 49, 50, 55, 56, 57, 60 alinéas 2 et 3, 89 alinéa 1, 91, 93 à 99 demeurent en vigueur jusqu\'à l\'adoption de la loi prévue par le nouvel article 90 alinéa 1. Toutefois, jusqu\'à cette date, le Grand Conseil est habilité à modifier l\'ordre et la numérotation de ces articles dans la mesure utile.', '109', 1, '');
        ";

        $conn->executeUpdate($sql);
    }

    public function insertArchives()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO archive VALUES(1, 2, 'Art. 2', '1974-03-17', '1993-08-01', 'révisé totalement');
            INSERT INTO archive VALUES(2, 2, 'Art. 2', '1990-06-10', '1993-08-01', 'révisé totalement');
            INSERT INTO archive VALUES(3, 14, 'Art. 13a', '2001-06-13', '2001-01-01', 'introduit');
            INSERT INTO archive VALUES(4, 22, 'Art. 21', '1976-09-26', '1977-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(5, 25, 'Art. 24 al. 1', '1920-11-11', '1921-01-07', 'modifié');
            INSERT INTO archive VALUES(6, 26, 'Art. 25', '2011-11-16', '2005-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(7, 29, 'Art. 28', '2006-12-14', '2008-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(8, 30, 'Art. 29 al. 1', '2006-12-14', '2008-01-01', 'modifié');
            INSERT INTO archive VALUES(9, 31, 'Art. 30', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(10, 31, 'Art. 30 al. 1, c), 3', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(11, 31, 'Art. 30 al. 1, d)', '1920-11-11', '1921-01-07', 'modifié');
            INSERT INTO archive VALUES(12, 31, 'Art. 30 al. 1, d)', '1973-09-23', '1973-10-14', 'modifié');
            INSERT INTO archive VALUES(13, 31, 'Art. 30 al. 1 , e)', '1920-11-11', '1921-01-07', 'abrogé');
            INSERT INTO archive VALUES(14, 32, 'Art. 31', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(15, 32, 'Art. 31 al. 2', '1972-09-24', '1973-07-08', 'modifié');
            INSERT INTO archive VALUES(16, 33, 'Art. 32', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(17, 34, 'Art. 33', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(18, 35, 'Art. 34', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(19, 36, 'Art. 35', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(20, 38, 'Art. 37', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(21, 39, 'Art. 38', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(22, 40, 'Art. 39', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(23, 40, 'Art. 39 al. 2', '2016-03-10', '2018-02-01', 'modifié');
            INSERT INTO archive VALUES(24, 41, 'Art. 40', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(25, 42, 'Art. 41', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(26, 43, 'Art. 42', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(27, 44, 'Art. 43', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(28, 44, 'Art. 43 al. 1', '1920-11-11', '1921-01-07', 'modifié');
            INSERT INTO archive VALUES(29, 45, 'Art. 44', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(30, 45, 'Art. 44 al. 1, b)', '2000-09-24', '2002-05-01', 'modifié');
            INSERT INTO archive VALUES(31, 45, 'Art. 44 al. 1, h)', '1920-11-11', '1921-01-07', 'abrogé');
            INSERT INTO archive VALUES(32, 45, 'Art. 44 al. 1, i)', '1920-11-11', '1921-01-07', 'abrogé');
            INSERT INTO archive VALUES(33, 46, 'Art. 45', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(34, 46, 'Art. 45', '2000-09-24', '2002-05-01', 'révisé totalement');
            INSERT INTO archive VALUES(35, 47, 'Art. 46', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(36, 48, 'Art. 47', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(37, 49, 'Art. 48', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(38, 50, 'Art. 49', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(39, 50, 'Art. 49', '2000-09-24', '2002-05-01', 'révisé totalement');
            INSERT INTO archive VALUES(40, 51, 'Art. 50', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(41, 52, 'Art. 51', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(42, 53, 'Art. 52 al. 2', '1920-11-11', '1921-01-07', 'modifié');
            INSERT INTO archive VALUES(43, 53, 'Art. 52 al. 3', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(44, 53, 'Art. 52 al. 4', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(45, 53, 'Art. 52 al. 5', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(46, 53, 'Art. 52 al. 6', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(47, 53, 'Art. 52 al. 6', '1996-01-21', '1997-02-01', 'modifié');
            INSERT INTO archive VALUES(48, 53, 'Art. 52 al. 7', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(49, 53, 'Art. 52 al. 7', '1996-01-21', '1997-02-01', 'modifié');
            INSERT INTO archive VALUES(50, 53, 'Art. 52 al. 8', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(51, 53, 'Art. 52 al. 9', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(52, 53, 'Art. 52 al. 10', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(53, 54, 'Art. 53', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(54, 55, 'Art. 54', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(55, 56, 'Art. 55', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(56, 57, 'Art. 56', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(57, 58, 'Art. 57', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(58, 59, 'Art. 58', '1993-10-24', '1998-07-01', 'révisé totalement');
            INSERT INTO archive VALUES(59, 60, 'Art. 59', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(60, 61, 'Art. 60 al. 2', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(61, 61, 'Art. 60 al. 3', '1993-10-20', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(62, 67, 'Art. 65a', '2016-03-10', '2018-02-01', 'introduit');
            INSERT INTO archive VALUES(63, 71, 'Art. 69 al. 1', '1975-09-28', '1981-01-01', 'modifié');
            INSERT INTO archive VALUES(64, 72, 'Art. 70', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(65, 73, 'Art. 71', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(66, 74, 'Art. 72', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(67, 75, 'Art. 73', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(68, 76, 'Art. 74', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(69, 77, 'Art. 75', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(70, 77, 'Art. 75 al. 3', '2004-05-13', '2006-02-01', 'modifié');
            INSERT INTO archive VALUES(71, 78, 'Art. 76', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(72, 78, 'Art. 76 al. 1, c)', '1990-08-01', '1993-08-01', 'abrogé');
            INSERT INTO archive VALUES(73, 79, 'Art. 77 ', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(74, 80, 'Art. 78', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(75, 80, 'Art. 78 al. 3, a)', '2004-05-13', '2006-02-01', 'modifié');
            INSERT INTO archive VALUES(76, 80, 'Art. 78 al. 3, b)', '2004-05-13', '2006-02-01', 'modifié');
            INSERT INTO archive VALUES(77, 80, 'Art. 78 al. 3, c)', '2004-05-13', '2006-02-01', 'modifié');
            INSERT INTO archive VALUES(78, 80, 'Art. 78 al. 3, d)', '2004-05-13', '2006-02-01', 'introduit');
            INSERT INTO archive VALUES(79, 81, 'Art. 79', '1975-05-13', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(80, 81, 'Art. 79 al. 1., e)', '2004-05-13', '2006-02-01', 'modifié');
            INSERT INTO archive VALUES(81, 82, 'Art. 80', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(82, 83, 'Art. 81', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(83, 84, 'Art. 82', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(84, 85, 'Art. 83', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(85, 85, 'Art. 83', '1990-06-10', '1993-08-01', 'abrogé');
            INSERT INTO archive VALUES(86, 86, 'Art. 84', '1985-06-09', '1987-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(87, 86, 'Art. 84 al. 1', '1912-06-23', '1912-10-25', 'modifié');
            INSERT INTO archive VALUES(88, 86, 'Art. 84 al. 1', '1952-12-21', '1953-01-23', 'modifié');
            INSERT INTO archive VALUES(89, 86, 'Art. 84 al. 2', '1912-06-23', '1912-10-25', 'modifié');
            INSERT INTO archive VALUES(90, 86, 'Art. 84 al. 2', '1952-12-21', '1953-01-23', 'modifié');
            INSERT INTO archive VALUES(91, 86, 'Art. 84 al. 4', '1919-11-20', '1920-03-13', 'modifié');
            INSERT INTO archive VALUES(92, 86, 'Art. 84 al. 4', '1952-12-21', '1953-01-23', 'modifié');
            INSERT INTO archive VALUES(93, 86, 'Art. 84 al. 5', '1919-11-20', '1920-03-13', 'modifié');
            INSERT INTO archive VALUES(94, 86, 'Art. 84 al. 5', '1952-12-21', '1953-01-23', 'abrogé');
            INSERT INTO archive VALUES(95, 86, 'Art. 84 al. 6', '1919-11-20', '1920-03-13', 'abrogé');
            INSERT INTO archive VALUES(96, 86, 'Art. 84 al. 7', '1919-11-20', '1920-03-13', 'abrogé');
            INSERT INTO archive VALUES(97, 86, 'Art. 84 al. 8', '1919-11-20', '1920-03-13', 'abrogé');
            INSERT INTO archive VALUES(98, 88, 'Art. 85a', '1920-11-11', '1921-01-07', 'introduit');
            INSERT INTO archive VALUES(99, 88, 'Art. 85a al. 1', '1994-03-11', '1934-07-06', 'modifié');
            INSERT INTO archive VALUES(100, 88, 'Art. 85a al. 2', '1996-01-21', '1997-02-01', 'modifié');
            INSERT INTO archive VALUES(101, 88, 'Art. 85a al. 3', '1996-01-21', '1997-02-01', 'modifié');
            INSERT INTO archive VALUES(102, 90, 'Art. 87', '1969-09-14', '1970-10-28', 'révisé totalement');
            INSERT INTO archive VALUES(103, 90, 'Art. 87', '2007-06-14', '2008-04-01', 'révisé totalement');
            INSERT INTO archive VALUES(104, 91, 'Art. 88', '1970-04-12', '1970-11-01', 'révisé totalement');
            INSERT INTO archive VALUES(105, 91, 'Art. 88 al. 1', '1991-06-02', '1991-08-16', 'modifié');
            INSERT INTO archive VALUES(106, 92, 'Art. 89', '1975-09-28', '1981-01-01', 'révisé totalement');
            INSERT INTO archive VALUES(107, 92, 'Art. 89', '1990-06-10', '1993-08-01', 'révisé totalement');
            INSERT INTO archive VALUES(108, 92, 'Art. 89 al. 1', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(109, 93, 'Art. 90', '1920-11-11', '1921-01-07', 'abrogé');
            INSERT INTO archive VALUES(110, 93, 'Art. 90', '1993-10-24', '1998-07-01', 'remis en vigueur');
            INSERT INTO archive VALUES(111, 94, 'Art. 91', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(112, 94, 'Art. 91 al. 1, a)', '1970-04-12', '1970-11-01', 'modifié');
            INSERT INTO archive VALUES(113, 94, 'Art. 91 al. 1, b)', '1970-04-12', '1970-04-12', 'modifié');
            INSERT INTO archive VALUES(114, 94, 'Art. 91 al. 1, c) ', '1970-04-12', '1970-11-01', 'modifié');
            INSERT INTO archive VALUES(115, 94, 'Art. 91 al. 1, d)', '1970-04-12', '1970-11-01', 'modifié');
            INSERT INTO archive VALUES(116, 94, 'Art. 91 al. 1, e)', '1970-04-12', '1970-11-01', 'abrogé');
            INSERT INTO archive VALUES(117, 94, 'Art. 91 al. 1, f)', '1970-04-12', '1970-11-01', 'abrogé');
            INSERT INTO archive VALUES(118, 94, 'Art. 91 al. 2', '1970-04-12', '1970-11-01', 'modifié');
            INSERT INTO archive VALUES(119, 94, 'Art. 91 al. 3', '1970-04-12', '1970-11-01', 'modifié');
            INSERT INTO archive VALUES(120, 96, 'Art. 93 ', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(121, 97, 'Art. 94', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(122, 98, 'Art. 95', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(123, 99, 'Art. 96', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(124, 100, 'Art. 97', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(125, 101, 'Art. 98', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(126, 102, 'Art. 99', '1993-10-24', '1998-07-01', 'abrogé');
            INSERT INTO archive VALUES(127, 103, 'Art. 100', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(128, 104, 'Art. 101', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(129, 104, 'Art. 101 al. 3', '1972-09-24', '1973-07-08', 'modifié');
            INSERT INTO archive VALUES(130, 105, 'Art. 102', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(131, 107, 'Art. 104 al. 2', '1993-10-24', '1994-06-01', 'modifié');
            INSERT INTO archive VALUES(132, 107, 'Art. 104 al. 3', '1993-10-24', '1994-06-01', 'introduit');
            INSERT INTO archive VALUES(133, 111, 'Art. 108', '1993-10-24', '1994-06-01', 'révisé totalement');
            INSERT INTO archive VALUES(134, 112, 'Art. 109', '1993-10-24', '1998-07-01', 'révisé totalement');
        ";

        $conn->executeUpdate($sql);
    }

    public function insertIntervenants()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO intervenant VALUES (1, 'M.', 'Roten', 'Heinrich von', '1856-02-15', '1916-12-18', 'conservateur', NULL, 'https://hls-dhs-dss.ch/fr/articles/004131/2010-11-12/', 'Raron (VS)');
            INSERT INTO intervenant VALUES (2, 'M.', 'Merio', 'Roger', NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (3, 'M.', 'Delacoste', 'Edmond', '1854-02-14', '1927-11-05', 'radical', 'img/intervenants/edmond_delacoste.jpeg', 'https://hls-dhs-dss.ch/fr/articles/004970/2002-05-22/', 'Monthey (VS)');
            INSERT INTO intervenant VALUES (4, 'M.', 'Torrenté', 'Henri De', '1845-12-06', '1922-01-20', 'conservateur', 'img/intervenants/henri_de_torrente.jpg', 'https://hls-dhs-dss.ch/fr/articles/004134/2012-10-26/', 'Naples (Campania, Italia)');
            INSERT INTO intervenant VALUES (5, 'M.', 'Evéquoz', 'Raymond', '1863-05-11', '1945-06-19', 'conservateur', NULL, 'https://hls-dhs-dss.ch/fr/articles/032714/2006-03-27/', 'Conthey (VS)');
            INSERT INTO intervenant VALUES (6, 'M.', 'Troillet', 'Maurice', '1880-06-17', '1961-08-20', 'conservateur progressiste', 'img/intervenants/maurice_troillet.jpg', 'https://hls-dhs-dss.ch/fr/articles/004971/2012-05-14/', 'Châble (comm. Bagnes, VS)');
            INSERT INTO intervenant VALUES (7, 'M.', 'Kluser', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (8, 'M.', 'Couchepin', 'Arthur', '1869-03-11', '1941-04-11', 'radical', 'img/intervenants/arthur_couchepin.jpg', 'https://hls-dhs-dss.ch/fr/articles/004968/2004-03-03/', 'Martigny-Bourg (VS)');
            INSERT INTO intervenant VALUES (9, 'M.', 'Bioley', 'Henri', '1841-08-13', '1913-05-23', 'conservateur', 'img/intervenants/henri_bioley.jpg', 'https://hls-dhs-dss.ch/fr/articles/004097/2004-09-30/', 'Forli (Emilia-Romagna, Italia)');
            INSERT INTO intervenant VALUES (10, 'M.', 'Delacoste', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (11, 'M.', 'Pllissier', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (12, 'M.', 'Bressoud', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (13, 'M.', 'Riedmatten', 'Raoul De', NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (14, 'M.', 'Burgener', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (15, 'M.', 'Werra', 'Raphael von', '1852-04-20', '1910-02-17', NULL, NULL, 'https://hls-dhs-dss.ch/fr/articles/021311/2013-04-03/', 'Brig (VS)');
            INSERT INTO intervenant VALUES (16, 'M.', 'De Riedmatten', 'Jacques', '1862-04-17', '1927-06-17', 'conservateur progressiste', 'img/intervenants/jacques_de_riedmatten.jpg', 'https://hls-dhs-dss.ch/fr/articles/030838/2009-06-25/', 'Sion (VS)');
            INSERT INTO intervenant VALUES (17, 'M.', 'Arlettaz', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (18, 'M.', 'Loretan', 'Gustav', '1848-11-03', '1932-07-24', 'conservateur', NULL, 'https://hls-dhs-dss.ch/fr/articles/004116/2008-07-09/', 'Loèche-les-Bains (VS)');
            INSERT INTO intervenant VALUES (19, 'M.', 'Defayes', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (20, 'M.', 'Zen Ruffinen', 'Julius', '1847-05-17', '1926-03-01', 'conservateur', NULL, 'https://hls-dhs-dss.ch/fr/articles/004137/2014-02-07/', 'Loèche (VS)');
            INSERT INTO intervenant VALUES (21, 'M.', 'Burgener', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (22, 'M.', 'Roten', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (23, 'M.', 'Kluser', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (24, 'M.', 'Pignat', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (25, 'M.', 'Abbet', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (26, 'M.', 'Biley', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
            INSERT INTO intervenant VALUES (27, 'M.', 'Kuntschen', 'Joseph', '1849-11-12', '1928-04-16', 'conservateur', NULL, 'https://hls-dhs-dss.ch/fr/articles/004115/2009-03-05/', 'Sion (VS)');
            INSERT INTO intervenant VALUES (28, 'M.', 'Stockalper', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
        ";

        $conn->executeUpdate($sql);
    }

    public function insertLois()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO loi VALUES(1, 2, '170.2', 'Loi sur l\'information du public, la protection des données et l\'archivage');
            INSERT INTO loi VALUES(2, 2, '180.1', 'Loi sur les rapports entre les Eglises et l\'Etat dans le canton du Valais');
            INSERT INTO loi VALUES(3, 5, '400.1', 'Loi sur l\'instruction publique');
            INSERT INTO loi VALUES(4, 2, '151.1', 'Loi concernant l\'application du principe d\'égalité entre femmes et hommes');
            INSERT INTO loi VALUES(5, 2, '177.7', 'Loi sur l\'assistance judiciaire');
            INSERT INTO loi VALUES(6, 4, '312.1', 'Loi concernant les dossiers de police judiciaire');
            INSERT INTO loi VALUES(7, 10, '932.1', 'Loi sur la prostitution');
            INSERT INTO loi VALUES(8, 8, '701.1', 'Loi d\'application de la loi fédérale sur l\'aménagement du territoire');
            INSERT INTO loi VALUES(9, 8, '705.1', 'Loi sur les constructions');
            INSERT INTO loi VALUES(10, 8, '710.1', 'Loi sur les expropriations');
            INSERT INTO loi VALUES(11, 8, '725.1', 'Loi sur les routes');
            INSERT INTO loi VALUES(12, 8, '740.1', 'Loi sur les transports publics');
            INSERT INTO loi VALUES(13, 2, '177.1', 'Loi sur la profession d\'avocat pratiquant la représentation en justice');
            INSERT INTO loi VALUES(14, 9, '822.20', 'Loi concernant l\'ouverture des magasins');
            INSERT INTO loi VALUES(15, 10, '930.1', 'Loi sur la police du commerce');
            INSERT INTO loi VALUES(18, 5, '411.0', 'Loi sur l\'enseignement primaire');
            INSERT INTO loi VALUES(19, 5, '411.2', 'Loi sur le cycle d\'orientation');
            INSERT INTO loi VALUES(20, 5, '411.3', 'Loi sur l\'enseignement spécialisé');
            INSERT INTO loi VALUES(21, 5, '412.1', 'Loi d\'application de la loi fédérale sur la formation professionnelle');
            INSERT INTO loi VALUES(22, 5, '414.70', 'Loi sur la Haute Ecole Spécialisée de Suisse occidentale Valais/Wallis');
            INSERT INTO loi VALUES(23, 5, '419.1', 'Loi concernant la Haute école pédagogique du Valais');
            INSERT INTO loi VALUES(24, 5, '420.1', 'Loi sur la formation et la recherche universitaires');
            INSERT INTO loi VALUES(25, 9, '822.1', 'Loi cantonale sur le travail');
            INSERT INTO loi VALUES(30, 10, '900.1', 'Loi sur la politique économique cantonale');
            INSERT INTO loi VALUES(31, 10, '901.1', 'Loi sur la politique régionale');
            INSERT INTO loi VALUES(32, 10, '921.1', 'Loi sur les forêts et les dangers naturels');
            INSERT INTO loi VALUES(33, 10, '935.1', 'Loi sur le tourisme');
            INSERT INTO loi VALUES(34, 10, '935.2', 'Loi sur les guides de montagne et les organisateurs d\'autres activités à risque');
            INSERT INTO loi VALUES(35, 10, '935.3', 'Loi sur l\'hébergement, la restauration et le commerce de détail de boissons alcoolisées');
            INSERT INTO loi VALUES(36, 8, '721.1', 'Loi sur l\'aménagement des cours d\'eau');
            INSERT INTO loi VALUES(39, 9, '850.4', 'Loi en faveur de la jeunesse');
            INSERT INTO loi VALUES(40, 9, '850.6', 'Loi sur l\'intégration des personnes handicapées');
            INSERT INTO loi VALUES(41, 9, '800.1', 'Loi sur la santé');
            INSERT INTO loi VALUES(42, 9, '800.10', 'Loi sur les établissements et institutions sanitaires');
            INSERT INTO loi VALUES(43, 9, '805.1', 'Loi sur les soins de longue durée');
            INSERT INTO loi VALUES(45, 2, '170.1', 'Loi sur la responsabilité des collectivités publiques et de leurs agents');
            INSERT INTO loi VALUES(46, 2, '112.1', 'Loi concernant les prestations à faire par la ville de Sion comme chef-lieu du canton');
            INSERT INTO loi VALUES(47, 2, '172.2', 'Loi sur le personnel de l\'Etat du Valais');
            INSERT INTO loi VALUES(48, 7, '642.1', 'Loi fiscale');
            INSERT INTO loi VALUES(49, 7, '643.1', 'Loi sur les droits de mutations');
            INSERT INTO loi VALUES(50, 7, '641.5', 'Loi sur l\'imposition des véhicules automobiles');
            INSERT INTO loi VALUES(51, 7, '641.52', 'Loi sur l\'imposition des bateaux');
            INSERT INTO loi VALUES(55, 6, '501.1', 'Loi sur la protection de la population et la gestion des situations particulières et extraordinaires');
            INSERT INTO loi VALUES(56, 7, '611.1', 'Loi sur la gestion et le contrôle administratifs et financiers du canton');
            INSERT INTO loi VALUES(57, 7, '612.1', 'Loi sur le frein aux dépenses et à l\'endettement');
            INSERT INTO loi VALUES(58, 7, '612.5', 'Loi concernant le financement des grands projets d\'infrastructures du 21e siècle');
            INSERT INTO loi VALUES(60, 2, '175.101', 'Loi sur la fusion des communes municipales et bourgeoisiales de Sion et des Agettes');
            INSERT INTO loi VALUES(61, 5, '413.10', 'Loi fixant la contribution des communes du siège des collèges et établissements cantonaux');
            INSERT INTO loi VALUES(62, 5, '417.10', 'Loi fixant la localisation des écoles cantonales du degré tertiaire et la contribution des communes sièges');
            INSERT INTO loi VALUES(64, 2, '141.1', 'Loi sur le droit de cité valaisan');
            INSERT INTO loi VALUES(67, 2, '171.1', 'Loi sur l\'organisation des Conseils et les rapports entre les pouvoirs');
            INSERT INTO loi VALUES(68, 3, '211.41', 'Loi réglant l\'application de la loi fédérale sur l\'acquisition d\'immeubles par des personnes à l\'étranger');
            INSERT INTO loi VALUES(69, 6, '520.3', 'Loi d\'application de la loi fédérale sur la protection des biens culturels en cas de conflit armé');
            INSERT INTO loi VALUES(72, 8, '701.2', 'Loi concernant le remembrement et la rectification de limites');
            INSERT INTO loi VALUES(73, 8, '701.6', 'Loi concernant la perception des contributions de propriétaires fonciers aux frais d\'équipements et aux frais d\'autres ouvrages publics');
            INSERT INTO loi VALUES(74, 8, '721.8', 'Loi sur l\'utilisation des forces hydrauliques');
            INSERT INTO loi VALUES(76, 8, '741.1', 'Loi d\'application de la législation fédérale sur la circulation routière');
            INSERT INTO loi VALUES(77, 8, '747.2', 'Loi d\'application de la loi fédérale sur la navigation intérieure et de l\'accord franco-suisse concernant la navigation sur le Léman');
            INSERT INTO loi VALUES(79, 9, '823.33', '');
            INSERT INTO loi VALUES(80, 9, '831.2', 'Loi d\'application de la loi fédérale sur l\'assurance invalidité');
            INSERT INTO loi VALUES(81, 9, '841.1', 'Loi sur le logement');
            INSERT INTO loi VALUES(82, 9, '850.3', 'Loi sur le recouvrement des pensions alimentaires et le versement d\'avances');
            INSERT INTO loi VALUES(83, 9, '857.1', 'Loi d\'application de la loi fédérale sur les centres de consultation en matière de grossesse');
            INSERT INTO loi VALUES(85, 10, '922.1', 'Loi sur la chasse et la protection des mammifères et oiseaux sauvages');
            INSERT INTO loi VALUES(86, 2, '110.010', 'Loi concernant la mise en œuvre de la réforme de la péréquation financière et de la répartition des tâches entre la Confédération, le canton et les communes');
            INSERT INTO loi VALUES(87, 2, '142.1', 'Loi d\'application de la loi fédérale sur les étrangers');
            INSERT INTO loi VALUES(89, 2, '160.3', 'Loi sur les incompatibilités');
            INSERT INTO loi VALUES(90, 2, '160.5', 'Ordonnance sur les incompatibilités');
            INSERT INTO loi VALUES(92, 2, '170.3', 'Loi sur les participations de l\'Etat à des personnes morales et autres entités');
            INSERT INTO loi VALUES(93, 2, '172.13', 'Loi sur la prévoyance professionnelle des magistrats');
            INSERT INTO loi VALUES(95, 2, '172.5', '');
            INSERT INTO loi VALUES(96, 2, '173.1', 'Loi sur l\'organisation de la Justice');
            INSERT INTO loi VALUES(97, 2, '173.12', 'Loi concernant le traitement des autorités judiciaires et des représentants du ministère public');
            INSERT INTO loi VALUES(98, 2, '175.1', 'Loi sur les communes');
            INSERT INTO loi VALUES(99, 2, '176.1', 'Loi sur le contrôle de l\'habitat');
            INSERT INTO loi VALUES(102, 2, '178.1', 'Loi sur le notariat');
            INSERT INTO loi VALUES(103, 3, '211.1', 'Loi d\'application du code civil suisse ');
            INSERT INTO loi VALUES(104, 3, '211.15', 'Loi d\'application de la loi fédérale sur le partenariat enregistré');
            INSERT INTO loi VALUES(105, 3, '211.412', 'Loi concernant l\'application du droit foncier rural');
            INSERT INTO loi VALUES(106, 3, '211.7', 'Loi d\'application de loi fédérale sur la géoinformation');
            INSERT INTO loi VALUES(107, 3, '221.21', 'Loi d\'application de la loi fédérale sur le crédit à la consommation');
            INSERT INTO loi VALUES(108, 3, '270.1', 'Loi d\'application du code de procédure civile suisse');
            INSERT INTO loi VALUES(109, 3, '281.1', 'Loi d\'application de la loi fédérale sur la poursuite pour dettes et la faillite');
            INSERT INTO loi VALUES(110, 4, '311.1', 'Loi d\'application du code pénal');
            INSERT INTO loi VALUES(111, 4, '312.0', 'Loi d\'application du code de procédure pénale suisse');
            INSERT INTO loi VALUES(112, 4, '312.5', 'Loi d\'application de la loi fédérale sur l\'aide aux victimes d\'infractions');
            INSERT INTO loi VALUES(113, 4, '314.1', 'Loi d\'application de la loi fédérale régissant la condition pénale des mineurs');
            INSERT INTO loi VALUES(114, 4, '314.2', 'Loi d\'application de la loi fédérale sur la procédure pénale applicable aux mineurs');
            INSERT INTO loi VALUES(115, 5, '400.2', 'Règlement concernant l\'éducation physique à l\'école');
            INSERT INTO loi VALUES(116, 5, '405.1', 'Loi sur la contribution des communes au traitement du personnel de la scolarité obligatoire et aux charges d\'exploitation des institutions spécialisées');
            INSERT INTO loi VALUES(117, 5, '405.3', 'Loi sur le traitement du personnel de la scolarité obligatoire et de l\'enseignement secondaire du deuxième degré général et professionnel');
            INSERT INTO loi VALUES(121, 5, '412.5', 'Loi sur le fonds cantonal en faveur de la formation professionnelle');
            INSERT INTO loi VALUES(123, 5, '415.1', 'Loi sur le sport');
            INSERT INTO loi VALUES(124, 5, '416.1', 'Loi sur les allocations de formation');
            INSERT INTO loi VALUES(125, 5, '417.4', 'Loi sur la formation continue des adultes');
            INSERT INTO loi VALUES(128, 5, '440.1', 'Loi sur la promotion de la culture');
            INSERT INTO loi VALUES(129, 5, '451.1', 'Loi sur la protection de la nature, du paysage et des sites');
            INSERT INTO loi VALUES(130, 5, '455.1', 'Ordonnance concernant la formation des nouveaux détenteurs de chiens');
            INSERT INTO loi VALUES(132, 6, '502.1', 'Loi d\'application de la loi fédérale sur les armes, les accessoires d\'armes et les munitions');
            INSERT INTO loi VALUES(133, 6, '504.1', 'Loi d\'application de la loi fédérale sur l\'armée et l\'administration militaire');
            INSERT INTO loi VALUES(134, 6, '520.1', 'Loi sur la protection civile');
            INSERT INTO loi VALUES(135, 6, '550.1', 'Loi sur la police cantonale');
            INSERT INTO loi VALUES(136, 6, '550.6', 'Loi sur les violences domestiques');
            INSERT INTO loi VALUES(139, 7, '613.1', 'Loi sur la péréquation financière intercommunale');
            INSERT INTO loi VALUES(142, 7, '658.1', 'Loi d\'application de la loi fédérale sur l\'impôt fédéral direct');
            INSERT INTO loi VALUES(143, 7, '660.1', 'Loi d\'application de la loi fédérale sur la taxe d\'exemption de l\'obligation de servir');
            INSERT INTO loi VALUES(144, 8, '704.1', 'Loi sur les itinéraires de mobilité de loisirs');
            INSERT INTO loi VALUES(148, 8, '726.1', 'Loi concernant l\'adhésion du canton du Valais à l\'accord intercantonal sur les marchés publics');
            INSERT INTO loi VALUES(149, 8, '730.1', 'Loi sur l\'énergie');
            INSERT INTO loi VALUES(150, 8, '731.1', 'Loi sur les Forces Motrices Valaisannes');
            INSERT INTO loi VALUES(155, 9, '810.8', 'Loi sur l\'organisation des secours sanitaires');
            INSERT INTO loi VALUES(156, 9, '813.10', 'Loi d\'application de la loi fédérale sur la protection contre les substances et les préparations dangereuses');
            INSERT INTO loi VALUES(157, 9, '813.5', 'Loi vétérinaire');
            INSERT INTO loi VALUES(158, 9, '814.1', 'Loi sur la protection de l\'environnement');
            INSERT INTO loi VALUES(159, 9, '814.3', 'Loi cantonale sur la protection des eaux');
            INSERT INTO loi VALUES(160, 9, '817.1', 'Loi concernant l\'application de la loi fédérale sur les denrées alimentaires et les objets usuels');
            INSERT INTO loi VALUES(162, 9, '831.1', 'Loi d\'application de la loi fédérale sur l\'assurance-vieillesse et survivants');
            INSERT INTO loi VALUES(163, 9, '831.3', 'Loi d\'application de la loi fédérale sur les prestations complémentaires à l\'AVS/AI');
            INSERT INTO loi VALUES(164, 9, '832.1', 'Loisur l\'assurance maladie');
            INSERT INTO loi VALUES(165, 9, '836.1', 'Loi d\'application de la loi fédérale sur les allocations familiales');
            INSERT INTO loi VALUES(166, 9, '837.1', 'Loi sur l\'empoi et les mesures en faveur des chômeurs');
            INSERT INTO loi VALUES(167, 9, '850.1', 'Loi sur l\'intégration et l\'aide sociale ');
            INSERT INTO loi VALUES(168, 9, '850.2', 'Loi sur l\'harmonisation du financement des régimes sociaux et d\'insertion socio-professionnelle');
            INSERT INTO loi VALUES(171, 10, '910.1', 'Loi sur l\'agriculture et le développement rural');
            INSERT INTO loi VALUES(172, 10, '916.4', 'Loi d\'application de la loi fédérale sur les épizooties');
            INSERT INTO loi VALUES(173, 10, '923.1', 'Loi cantonale sur la pêche');
            INSERT INTO loi VALUES(178, 10, '935.52', 'Loi d\'application de la loi fédérale sur les jeux de hasard et les maisons de jeu');
            INSERT INTO loi VALUES(179, 10, '941.2', 'Loi d\'application de la loi fédérale sur la métrologie');
            INSERT INTO loi VALUES(180, 10, '946.2', 'Loi concernant la reconnaissance des formations professionnelles des ressortissants des Etats membres de la Communauté européenne');
            INSERT INTO loi VALUES(181, 2, '111.010', 'Loi sur le sceau de la République');
            INSERT INTO loi VALUES(183, 8, '734.1', 'Loi cantonale sur l\'approvisionnement en électricité');
            INSERT INTO loi VALUES(184, 2, '160.1', 'Loi sur les droits politiques');
            INSERT INTO loi VALUES(185, 2, '172.15', 'Loi sur l\'organisation et les attributions des conseils de districts');
            INSERT INTO loi VALUES(187, 2, '172.16', 'Loi sur les attributions des préfets');
            INSERT INTO loi VALUES(194, 9, '813.2', 'Convention intercantonale sur l\'Hôpital Riviera-Chablais Vaud et Valais');
            INSERT INTO loi VALUES(202, 2, '172.12', 'Loi concernant les traitements des magistrats de l\'ordre exécutif');
            INSERT INTO loi VALUES(204, 2, '172.4', 'Loi fixant le traitement des employés de l\'Etat du Valais');
            INSERT INTO loi VALUES(206, 5, '417.03', 'Loi fixant le traitement du personnel des écoles de formation professionnelle supérieure');
            INSERT INTO loi VALUES(222, 2, '173.8', 'Loi fixant le tarif des frais et dépens devant les autorités judiciaires ou administratives');
            INSERT INTO loi VALUES(231, 3, '211.6', 'Loi sur la mensuration officielle');
            INSERT INTO loi VALUES(317, 2, '170.7', 'Loi sur l\'organisation de la cour chargée de statuer sur les conflits de compétence entre le pouvoir administratif et le pouvoir judiciaire');
            INSERT INTO loi VALUES(326, 2, '172.61', 'Loi supprimant une instance de recours administratif');
            INSERT INTO loi VALUES(328, 9, '850.610', 'Ordonnance sur l\'organisation et le fonctionnement de La Castalie');
            INSERT INTO loi VALUES(348, 2, '172.6', 'Loi sur la procédure et la juridiction administratives');
            INSERT INTO loi VALUES(364, 2, '175.2', 'Loi sur les bourgeoisies');
        ";

        $conn->executeUpdate($sql);
    }

    public function insertArticlesLois()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO article_loi VALUES (1, 1);
            INSERT INTO article_loi VALUES (2, 2);
            INSERT INTO article_loi VALUES (2, 3);
            INSERT INTO article_loi VALUES (3, 4);
            INSERT INTO article_loi VALUES (3, 5);
            INSERT INTO article_loi VALUES (4, 6);
            INSERT INTO article_loi VALUES (4, 7);
            INSERT INTO article_loi VALUES (6, 8);
            INSERT INTO article_loi VALUES (6, 9);
            INSERT INTO article_loi VALUES (6, 10);
            INSERT INTO article_loi VALUES (6, 11);
            INSERT INTO article_loi VALUES (6, 12);
            INSERT INTO article_loi VALUES (10, 7);
            INSERT INTO article_loi VALUES (10, 13);
            INSERT INTO article_loi VALUES (10, 14);
            INSERT INTO article_loi VALUES (10, 15);
            INSERT INTO article_loi VALUES (13, 3);
            INSERT INTO article_loi VALUES (13, 18);
            INSERT INTO article_loi VALUES (13, 19);
            INSERT INTO article_loi VALUES (13, 20);
            INSERT INTO article_loi VALUES (13, 21);
            INSERT INTO article_loi VALUES (13, 22);
            INSERT INTO article_loi VALUES (13, 23);
            INSERT INTO article_loi VALUES (13, 24);
            INSERT INTO article_loi VALUES (15, 25);
            INSERT INTO article_loi VALUES (16, 3);
            INSERT INTO article_loi VALUES (16, 21);
            INSERT INTO article_loi VALUES (16, 22);
            INSERT INTO article_loi VALUES (16, 24);
            INSERT INTO article_loi VALUES (16, 30);
            INSERT INTO article_loi VALUES (16, 31);
            INSERT INTO article_loi VALUES (16, 32);
            INSERT INTO article_loi VALUES (16, 33);
            INSERT INTO article_loi VALUES (16, 34);
            INSERT INTO article_loi VALUES (16, 35);
            INSERT INTO article_loi VALUES (18, 11);
            INSERT INTO article_loi VALUES (18, 36);
            INSERT INTO article_loi VALUES (19, 3);
            INSERT INTO article_loi VALUES (19, 39);
            INSERT INTO article_loi VALUES (19, 40);
            INSERT INTO article_loi VALUES (20, 41);
            INSERT INTO article_loi VALUES (20, 42);
            INSERT INTO article_loi VALUES (20, 43);
            INSERT INTO article_loi VALUES (21, 40);
            INSERT INTO article_loi VALUES (22, 45);
            INSERT INTO article_loi VALUES (23, 46);
            INSERT INTO article_loi VALUES (23, 47);
            INSERT INTO article_loi VALUES (24, 48);
            INSERT INTO article_loi VALUES (24, 49);
            INSERT INTO article_loi VALUES (25, 33);
            INSERT INTO article_loi VALUES (25, 34);
            INSERT INTO article_loi VALUES (25, 48);
            INSERT INTO article_loi VALUES (25, 50);
            INSERT INTO article_loi VALUES (25, 51);
            INSERT INTO article_loi VALUES (26, 55);
            INSERT INTO article_loi VALUES (26, 56);
            INSERT INTO article_loi VALUES (26, 57);
            INSERT INTO article_loi VALUES (26, 58);
            INSERT INTO article_loi VALUES (27, 46);
            INSERT INTO article_loi VALUES (27, 60);
            INSERT INTO article_loi VALUES (28, 23);
            INSERT INTO article_loi VALUES (28, 61);
            INSERT INTO article_loi VALUES (28, 62);
            INSERT INTO article_loi VALUES (29, 64);
            INSERT INTO article_loi VALUES (30, 64);
            INSERT INTO article_loi VALUES (31, 8);
            INSERT INTO article_loi VALUES (31, 11);
            INSERT INTO article_loi VALUES (31, 25);
            INSERT INTO article_loi VALUES (31, 32);
            INSERT INTO article_loi VALUES (31, 51);
            INSERT INTO article_loi VALUES (31, 64);
            INSERT INTO article_loi VALUES (31, 67);
            INSERT INTO article_loi VALUES (31, 68);
            INSERT INTO article_loi VALUES (31, 69);
            INSERT INTO article_loi VALUES (31, 72);
            INSERT INTO article_loi VALUES (31, 73);
            INSERT INTO article_loi VALUES (31, 74);
            INSERT INTO article_loi VALUES (31, 76);
            INSERT INTO article_loi VALUES (31, 77);
            INSERT INTO article_loi VALUES (31, 80);
            INSERT INTO article_loi VALUES (31, 81);
            INSERT INTO article_loi VALUES (31, 82);
            INSERT INTO article_loi VALUES (31, 83);
            INSERT INTO article_loi VALUES (31, 85);
            INSERT INTO article_loi VALUES (32, 1);
            INSERT INTO article_loi VALUES (32, 4);
            INSERT INTO article_loi VALUES (32, 5);
            INSERT INTO article_loi VALUES (32, 9);
            INSERT INTO article_loi VALUES (32, 10);
            INSERT INTO article_loi VALUES (32, 12);
            INSERT INTO article_loi VALUES (32, 13);
            INSERT INTO article_loi VALUES (32, 14);
            INSERT INTO article_loi VALUES (32, 15);
            INSERT INTO article_loi VALUES (32, 18);
            INSERT INTO article_loi VALUES (32, 19);
            INSERT INTO article_loi VALUES (32, 21);
            INSERT INTO article_loi VALUES (32, 22);
            INSERT INTO article_loi VALUES (32, 23);
            INSERT INTO article_loi VALUES (32, 24);
            INSERT INTO article_loi VALUES (32, 30);
            INSERT INTO article_loi VALUES (32, 31);
            INSERT INTO article_loi VALUES (32, 33);
            INSERT INTO article_loi VALUES (32, 34);
            INSERT INTO article_loi VALUES (32, 35);
            INSERT INTO article_loi VALUES (32, 36);
            INSERT INTO article_loi VALUES (32, 41);
            INSERT INTO article_loi VALUES (32, 42);
            INSERT INTO article_loi VALUES (32, 43);
            INSERT INTO article_loi VALUES (32, 47);
            INSERT INTO article_loi VALUES (32, 49);
            INSERT INTO article_loi VALUES (32, 50);
            INSERT INTO article_loi VALUES (32, 55);
            INSERT INTO article_loi VALUES (32, 57);
            INSERT INTO article_loi VALUES (32, 58);
            INSERT INTO article_loi VALUES (32, 74);
            INSERT INTO article_loi VALUES (32, 86);
            INSERT INTO article_loi VALUES (32, 87);
            INSERT INTO article_loi VALUES (32, 89);
            INSERT INTO article_loi VALUES (32, 90);
            INSERT INTO article_loi VALUES (32, 92);
            INSERT INTO article_loi VALUES (32, 93);
            INSERT INTO article_loi VALUES (32, 96);
            INSERT INTO article_loi VALUES (32, 97);
            INSERT INTO article_loi VALUES (32, 98);
            INSERT INTO article_loi VALUES (32, 99);
            INSERT INTO article_loi VALUES (32, 102);
            INSERT INTO article_loi VALUES (32, 103);
            INSERT INTO article_loi VALUES (32, 104);
            INSERT INTO article_loi VALUES (32, 105);
            INSERT INTO article_loi VALUES (32, 106);
            INSERT INTO article_loi VALUES (32, 107);
            INSERT INTO article_loi VALUES (32, 108);
            INSERT INTO article_loi VALUES (32, 109);
            INSERT INTO article_loi VALUES (32, 110);
            INSERT INTO article_loi VALUES (32, 111);
            INSERT INTO article_loi VALUES (32, 112);
            INSERT INTO article_loi VALUES (32, 113);
            INSERT INTO article_loi VALUES (32, 114);
            INSERT INTO article_loi VALUES (32, 115);
            INSERT INTO article_loi VALUES (32, 116);
            INSERT INTO article_loi VALUES (32, 117);
            INSERT INTO article_loi VALUES (32, 121);
            INSERT INTO article_loi VALUES (32, 123);
            INSERT INTO article_loi VALUES (32, 124);
            INSERT INTO article_loi VALUES (32, 125);
            INSERT INTO article_loi VALUES (32, 128);
            INSERT INTO article_loi VALUES (32, 129);
            INSERT INTO article_loi VALUES (32, 130);
            INSERT INTO article_loi VALUES (32, 132);
            INSERT INTO article_loi VALUES (32, 133);
            INSERT INTO article_loi VALUES (32, 134);
            INSERT INTO article_loi VALUES (32, 135);
            INSERT INTO article_loi VALUES (32, 136);
            INSERT INTO article_loi VALUES (32, 139);
            INSERT INTO article_loi VALUES (32, 142);
            INSERT INTO article_loi VALUES (32, 143);
            INSERT INTO article_loi VALUES (32, 144);
            INSERT INTO article_loi VALUES (32, 148);
            INSERT INTO article_loi VALUES (32, 149);
            INSERT INTO article_loi VALUES (32, 150);
            INSERT INTO article_loi VALUES (32, 155);
            INSERT INTO article_loi VALUES (32, 156);
            INSERT INTO article_loi VALUES (32, 157);
            INSERT INTO article_loi VALUES (32, 158);
            INSERT INTO article_loi VALUES (32, 159);
            INSERT INTO article_loi VALUES (32, 160);
            INSERT INTO article_loi VALUES (32, 162);
            INSERT INTO article_loi VALUES (32, 163);
            INSERT INTO article_loi VALUES (32, 164);
            INSERT INTO article_loi VALUES (32, 165);
            INSERT INTO article_loi VALUES (32, 166);
            INSERT INTO article_loi VALUES (32, 167);
            INSERT INTO article_loi VALUES (32, 168);
            INSERT INTO article_loi VALUES (32, 171);
            INSERT INTO article_loi VALUES (32, 172);
            INSERT INTO article_loi VALUES (32, 173);
            INSERT INTO article_loi VALUES (32, 178);
            INSERT INTO article_loi VALUES (32, 179);
            INSERT INTO article_loi VALUES (32, 180);
            INSERT INTO article_loi VALUES (33, 110);
            INSERT INTO article_loi VALUES (33, 181);
            INSERT INTO article_loi VALUES (33, 183);
            INSERT INTO article_loi VALUES (35, 184);
            INSERT INTO article_loi VALUES (36, 185);
            INSERT INTO article_loi VALUES (37, 185);
            INSERT INTO article_loi VALUES (37, 187);
            INSERT INTO article_loi VALUES (38, 185);
            INSERT INTO article_loi VALUES (39, 22);
            INSERT INTO article_loi VALUES (39, 31);
            INSERT INTO article_loi VALUES (39, 33);
            INSERT INTO article_loi VALUES (39, 34);
            INSERT INTO article_loi VALUES (39, 64);
            INSERT INTO article_loi VALUES (39, 139);
            INSERT INTO article_loi VALUES (39, 185);
            INSERT INTO article_loi VALUES (39, 194);
            INSERT INTO article_loi VALUES (40, 184);
            INSERT INTO article_loi VALUES (40, 185);
            INSERT INTO article_loi VALUES (41, 92);
            INSERT INTO article_loi VALUES (41, 185);
            INSERT INTO article_loi VALUES (42, 97);
            INSERT INTO article_loi VALUES (42, 185);
            INSERT INTO article_loi VALUES (42, 202);
            INSERT INTO article_loi VALUES (42, 204);
            INSERT INTO article_loi VALUES (42, 206);
            INSERT INTO article_loi VALUES (43, 1);
            INSERT INTO article_loi VALUES (43, 4);
            INSERT INTO article_loi VALUES (43, 5);
            INSERT INTO article_loi VALUES (43, 9);
            INSERT INTO article_loi VALUES (43, 13);
            INSERT INTO article_loi VALUES (43, 14);
            INSERT INTO article_loi VALUES (43, 18);
            INSERT INTO article_loi VALUES (43, 19);
            INSERT INTO article_loi VALUES (43, 21);
            INSERT INTO article_loi VALUES (43, 22);
            INSERT INTO article_loi VALUES (43, 24);
            INSERT INTO article_loi VALUES (43, 35);
            INSERT INTO article_loi VALUES (43, 36);
            INSERT INTO article_loi VALUES (43, 41);
            INSERT INTO article_loi VALUES (43, 42);
            INSERT INTO article_loi VALUES (43, 43);
            INSERT INTO article_loi VALUES (43, 47);
            INSERT INTO article_loi VALUES (43, 49);
            INSERT INTO article_loi VALUES (43, 50);
            INSERT INTO article_loi VALUES (43, 55);
            INSERT INTO article_loi VALUES (43, 57);
            INSERT INTO article_loi VALUES (43, 64);
            INSERT INTO article_loi VALUES (43, 87);
            INSERT INTO article_loi VALUES (43, 89);
            INSERT INTO article_loi VALUES (43, 92);
            INSERT INTO article_loi VALUES (43, 93);
            INSERT INTO article_loi VALUES (43, 95);
            INSERT INTO article_loi VALUES (43, 96);
            INSERT INTO article_loi VALUES (43, 97);
            INSERT INTO article_loi VALUES (43, 98);
            INSERT INTO article_loi VALUES (43, 99);
            INSERT INTO article_loi VALUES (43, 102);
            INSERT INTO article_loi VALUES (43, 103);
            INSERT INTO article_loi VALUES (43, 104);
            INSERT INTO article_loi VALUES (43, 105);
            INSERT INTO article_loi VALUES (43, 106);
            INSERT INTO article_loi VALUES (43, 107);
            INSERT INTO article_loi VALUES (43, 108);
            INSERT INTO article_loi VALUES (43, 109);
            INSERT INTO article_loi VALUES (43, 110);
            INSERT INTO article_loi VALUES (43, 111);
            INSERT INTO article_loi VALUES (43, 112);
            INSERT INTO article_loi VALUES (43, 113);
            INSERT INTO article_loi VALUES (43, 114);
            INSERT INTO article_loi VALUES (43, 115);
            INSERT INTO article_loi VALUES (43, 116);
            INSERT INTO article_loi VALUES (43, 117);
            INSERT INTO article_loi VALUES (43, 123);
            INSERT INTO article_loi VALUES (43, 124);
            INSERT INTO article_loi VALUES (43, 125);
            INSERT INTO article_loi VALUES (43, 128);
            INSERT INTO article_loi VALUES (43, 129);
            INSERT INTO article_loi VALUES (43, 130);
            INSERT INTO article_loi VALUES (43, 132);
            INSERT INTO article_loi VALUES (43, 133);
            INSERT INTO article_loi VALUES (43, 134);
            INSERT INTO article_loi VALUES (43, 135);
            INSERT INTO article_loi VALUES (43, 136);
            INSERT INTO article_loi VALUES (43, 139);
            INSERT INTO article_loi VALUES (43, 142);
            INSERT INTO article_loi VALUES (43, 143);
            INSERT INTO article_loi VALUES (43, 148);
            INSERT INTO article_loi VALUES (43, 149);
            INSERT INTO article_loi VALUES (43, 150);
            INSERT INTO article_loi VALUES (43, 155);
            INSERT INTO article_loi VALUES (43, 156);
            INSERT INTO article_loi VALUES (43, 157);
            INSERT INTO article_loi VALUES (43, 158);
            INSERT INTO article_loi VALUES (43, 159);
            INSERT INTO article_loi VALUES (43, 160);
            INSERT INTO article_loi VALUES (43, 162);
            INSERT INTO article_loi VALUES (43, 163);
            INSERT INTO article_loi VALUES (43, 164);
            INSERT INTO article_loi VALUES (43, 165);
            INSERT INTO article_loi VALUES (43, 166);
            INSERT INTO article_loi VALUES (43, 167);
            INSERT INTO article_loi VALUES (43, 168);
            INSERT INTO article_loi VALUES (43, 171);
            INSERT INTO article_loi VALUES (43, 172);
            INSERT INTO article_loi VALUES (43, 173);
            INSERT INTO article_loi VALUES (43, 178);
            INSERT INTO article_loi VALUES (43, 179);
            INSERT INTO article_loi VALUES (43, 180);
            INSERT INTO article_loi VALUES (43, 181);
            INSERT INTO article_loi VALUES (43, 183);
            INSERT INTO article_loi VALUES (43, 185);
            INSERT INTO article_loi VALUES (43, 202);
            INSERT INTO article_loi VALUES (43, 204);
            INSERT INTO article_loi VALUES (43, 206);
            INSERT INTO article_loi VALUES (43, 222);
            INSERT INTO article_loi VALUES (43, 231);
            INSERT INTO article_loi VALUES (44, 185);
            INSERT INTO article_loi VALUES (45, 8);
            INSERT INTO article_loi VALUES (45, 32);
            INSERT INTO article_loi VALUES (45, 56);
            INSERT INTO article_loi VALUES (45, 68);
            INSERT INTO article_loi VALUES (45, 72);
            INSERT INTO article_loi VALUES (45, 73);
            INSERT INTO article_loi VALUES (45, 74);
            INSERT INTO article_loi VALUES (45, 83);
            INSERT INTO article_loi VALUES (45, 85);
            INSERT INTO article_loi VALUES (45, 185);
            INSERT INTO article_loi VALUES (46, 185);
            INSERT INTO article_loi VALUES (47, 185);
            INSERT INTO article_loi VALUES (48, 185);
            INSERT INTO article_loi VALUES (49, 185);
            INSERT INTO article_loi VALUES (50, 185);
            INSERT INTO article_loi VALUES (51, 185);
            INSERT INTO article_loi VALUES (52, 185);
            INSERT INTO article_loi VALUES (53, 184);
            INSERT INTO article_loi VALUES (54, 56);
            INSERT INTO article_loi VALUES (54, 317);
            INSERT INTO article_loi VALUES (55, 56);
            INSERT INTO article_loi VALUES (55, 105);
            INSERT INTO article_loi VALUES (55, 139);
            INSERT INTO article_loi VALUES (55, 148);
            INSERT INTO article_loi VALUES (55, 149);
            INSERT INTO article_loi VALUES (55, 150);
            INSERT INTO article_loi VALUES (55, 160);
            INSERT INTO article_loi VALUES (56, 326);
            INSERT INTO article_loi VALUES (57, 135);
            INSERT INTO article_loi VALUES (58, 34);
            INSERT INTO article_loi VALUES (58, 328);
            INSERT INTO article_loi VALUES (59, 56);
            INSERT INTO article_loi VALUES (59, 105);
            INSERT INTO article_loi VALUES (59, 148);
            INSERT INTO article_loi VALUES (59, 149);
            INSERT INTO article_loi VALUES (59, 160);
            INSERT INTO article_loi VALUES (61, 96);
            INSERT INTO article_loi VALUES (61, 97);
            INSERT INTO article_loi VALUES (62, 96);
            INSERT INTO article_loi VALUES (62, 97);
            INSERT INTO article_loi VALUES (63, 96);
            INSERT INTO article_loi VALUES (63, 97);
            INSERT INTO article_loi VALUES (63, 222);
            INSERT INTO article_loi VALUES (64, 96);
            INSERT INTO article_loi VALUES (64, 97);
            INSERT INTO article_loi VALUES (64, 222);
            INSERT INTO article_loi VALUES (65, 25);
            INSERT INTO article_loi VALUES (65, 96);
            INSERT INTO article_loi VALUES (65, 97);
            INSERT INTO article_loi VALUES (67, 96);
            INSERT INTO article_loi VALUES (67, 97);
            INSERT INTO article_loi VALUES (67, 348);
            INSERT INTO article_loi VALUES (71, 8);
            INSERT INTO article_loi VALUES (71, 11);
            INSERT INTO article_loi VALUES (71, 12);
            INSERT INTO article_loi VALUES (71, 32);
            INSERT INTO article_loi VALUES (71, 129);
            INSERT INTO article_loi VALUES (73, 8);
            INSERT INTO article_loi VALUES (73, 32);
            INSERT INTO article_loi VALUES (73, 129);
            INSERT INTO article_loi VALUES (77, 98);
            INSERT INTO article_loi VALUES (79, 139);
            INSERT INTO article_loi VALUES (80, 139);
            INSERT INTO article_loi VALUES (80, 183);
            INSERT INTO article_loi VALUES (81, 139);
            INSERT INTO article_loi VALUES (82, 32);
            INSERT INTO article_loi VALUES (82, 364);
            INSERT INTO article_loi VALUES (84, 364);
            INSERT INTO article_loi VALUES (86, 184);
            INSERT INTO article_loi VALUES (91, 184);
            INSERT INTO article_loi VALUES (93, 90);
            INSERT INTO article_loi VALUES (95, 184);
            INSERT INTO article_loi VALUES (103, 67);
            INSERT INTO article_loi VALUES (103, 184);
            INSERT INTO article_loi VALUES (104, 67);
            INSERT INTO article_loi VALUES (105, 67);
            INSERT INTO article_loi VALUES (107, 67);
            INSERT INTO article_loi VALUES (108, 67);
            INSERT INTO article_loi VALUES (109, 67);
            INSERT INTO article_loi VALUES (110, 67);
            INSERT INTO article_loi VALUES (110, 184);
        ";

        $conn->executeUpdate($sql);
    }

    public function insertDebats()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO debat VALUES (1, 'Tous les citoyens sont egaux devant la loi.  II n\'y a en Valais aucun privilege de lieu de naissance, de personnes et de famille.» M. Henri Roten propose de biffer le second •alinéa qui n\'a pas sa raison d\'être attendu qu\'il n\'y a jamais eu aucune espèce de privilège de ce genre en Valais, si haut que Ton remonte dans l\'histoire. Si cela existe dans d\'autres cantons, ce n\'est pas le cas chez nous et ceci n\'est qu\'une copie d\'autres constitutions, mais n\'est pas applicable au Valais, c\'est contraire ä l\'histoire. Je vous prie de me citer une famille qui depuis 300 ans alt joui d\'un privilège. M. Roger Merio demande, au contraire, le maintien de cette disposition inscrite dans les sept ou huit Constitutions qui ont régi jusqu\'à présent notre républiques, nos ancêtres ont certainement eu leurs raisons pour inscrire dans la Charte fondamentale du pays cette affirmation que dans une république démocratique il ne saurait y avoir aucun privilège de lieu, de naissance, de personne et de famille. M. Delacoste demande aussi le maintien de ce second alinéa qui, dit-il avec raison, n\'est que la reproduction de l’article 4 de la Constitution fédérale.', '1907-02-25', 3);
            INSERT INTO debat VALUES (2, '« Tous les Suisses sont égaux devant la loi. II n\'y a, en Suisse, ni sujets, ni Privilèges de lieu, de naissance, de personne ou de famille.» I. Henri Roten. J\'ai parlé du Valais ; la Suisse n\'est pas le Valais. II peut y avoir des privilèges dans d\'autres cantons. LU vote, la proposition de M. Henri Roten rejetée. Les art. 4, 5, 6 et 7 sont ensuite adoptes si Observation.', '1907-02-25', 4);
            INSERT INTO debat VALUES (3, 'La liberté de manifester son inion verbalement ou par écrit, ainsi que liberté de la presse sont garanties. « La loi en réprime les abus. » La Commission propose de reprendre la ^Position faite par M. Seiler en premiers bats, tendant ä ce que mention soit faite protocole du désir de l\'Assemblée de voir citer une loi spéciale sur la presse. M. Henri de Torrente votera la proposition ! la Commission. Mais il tient d\'ores et déjà déclarer qu\'il ne veut, pour ou contre la \'esse, ni Privilèges ni lois d\'exception. La \'esse, dont il reconnait la puissance extraordinaire, doit être libre comme tout citoyen valaisan, comme toute personne morale ; mais le doit aussi être soumise sans restriction \'aucune sorte au Code penal qui nous régit, \'est dans ce sens seulement qu\'il salue l\'e-iboration d\'une loi sur la presse, qui est appelée à trancher plusieurs points importants, notamment en ce qui concerne le droit de réponse et la responsabilité des divers Organes d\'un Journal, rédacteurs, imprimeurs, gérants et correspondants. M. Raymond Evöquoz, Président de la Commission, estime que le rapporteur français a commis une légère erreur en formulant la proposition de la Commission. II n\'a pas été, croit-il, dans l\'idée de celle-ci de demander l\'étude immédiate d\'un projet de loi sur la presse, mais bien de préciser au protocole que tant qu\'une loi de ce genre ne sera pas promulguée le droit commun sera seul applicable aux délits de presse. Et cette mention est nécessitée par le fait que la Constitution fédérale proclame ä l\'article 55 la liberté de la presse et qu\'elle spécifie que les lois cantonales en répriment les abus. Or le Valais n\'a pas de loi spéciale sur la matière. II est dès lors de toute nécessite de bien marquer que le droit commun est applicable aux délits de presse. Cependant il ne s\'oppose pas à la proposition formulée par le rapporteur français, laquelle n\'est point en contradiction du reste avec celle qu\'il vient d\'indiquer comme ayant été émise, croit-il, par la Commission. M. Troillet, rapporteur, répond qu\'en Commission il a été décidé de maintenir la mention votée déjà en premiers débats, est-il donne en conséquence lecture du passage y relatif du Bulletin des séances de la Session prorogée de novembre 1905. L\'insertion votée alors avait été proposée par M. Evéquoz et disait « qu’aussi longtemps qu\'il n\'y aura pas une loi spéciale sur la presse les abus de celle-ci sont soumis aux dispositions du Code pénal.» M- Lion Martin préfère le texte propose par le rapporteur de la Commission que celui propose par le Président, M. Evequoz; nous voulons que Je Conseil d\'Etat soit invité à proposer au plus tôt possible un projet de loi sur la presse. M. Jos. Burgener. Président du Conseil d\'Etat. Dans une séance spéciale, le Conseil d\'Etat a examiné les différentes propositions-de la Commission. 11 s\'est occupé entre autres de la mention au protocole qui vient d\'être discutée. Si le Conseil d\'Etat a bien compris l\'insertion demande par la Commission, il serait appelé à étudier la question de savoir s\'il n\'y aurait pas lieu de présenter dans un temps plus ou moins rapproche un projet de loi sur la presse. En attendant les délits de presse seraient régis par le droit commun. Aussi bien qu\'ici, au sein du Conseil d\'Etat, les manières de voir sont partage. II est des membres qui se sont franchement prononces en faveur d\'une loi spéciale ; d\'autres, par contre, n\'en veulent rien. II y a dans Lette question du pour et contre. Par la loi pénale, les responsabilités en cas de délit de presse ne sont pas bien déterminées ; il est parfois difficile d\'établir la part des responsabilités du rédacteur et de l\'éditeur. Une loi spéciale pourrait trancher ces difficultés. D\'un autre côte, pourquoi stipuler des pénalités peut-être plus sévères pour la presse ? Pourquoi ne pas lui appliquer la loi de tout le monde ? Malgré des divergences de vues, le Conseil d\'Etat croit pouvoir accueillir la mention au protocole proposée par la Commission, la question demeurant par la même intacte. M. H. de Torrente\" votera cette proposition s\'il n\'est rien préjuge. M. Kluser. M. le président de la Commission a dit que si nous insérions cette clause au protocole il n\'y aurait rien de préjuge, c\'est sous cette réserve que je la voterai. M. Couchepin, conseiller d\'Etat, chef du Département de Justice et Police, termine la discussion par un chaud plaidoyer en faveur de cette loi. Je suis d\'avis, dit-il, qu\'une loi spéciale doit être élaborée, car il y a eu matière de presse des questions qui sont très compliquées ; plusieurs d\'entre elles ne sont pas prévues par le code pénal ; par exemple, le code pénal ne prévoit rien en ce qui concerne l\'échelle de responsabilité entre le correspondant, le rédacteur et l\'imprimeur; ce sont des questions très controversées dans la plupart des cas. L\'orateur accepte qu\'il soit inséré au protocole que jusqu\'ä ce que la loi spéciale existe, c\'est le droit qui est applicable. Au vote l\'art. 8 est adopte sans changement et il est décidé conformément à la proposition de la Commission d\'insérer au protocole la réserve déjà admise en premiers débats et portant qu\'aussi longtemps qu\'il n\'y aura pas une loi spéciale sur la presse, les abus de celle-ci sont soumis aux dispositions du code pénal.', '1907-02-25', 8);
            INSERT INTO debat VALUES (4, 'Le droit de libre établissement d\'association et de réunion, le libre exercice des professions libérales, la liberté du commerce et de l\'industrie sont garantis. « L\'exercice de ces droits est réglé par la loi dans les limites de la Constitution fédérale. » M. H. de Torrente rappelle que cette disposition ne répond pas à la réalité des faits, puisque l\'exercice de certaines professions est soumis, en Valais, à l\'autorisation du Conseil d\'Etat, par exemple les professions d\'avocat, notaire, médecin, pharmacien; M. de Torrente demande la suppression des mots «dans la limite de |la Constitution fédérale » attendu que la Constitution fédérale laisse aux cantons tonte compétence à cet égard et ne fixe pas des limites exactes pour la liberté d\'exercer les professions libérales. M. Evequoz fait ressortir que d\'une manière générale l\'exercice des professions libérales est libre, par exemple, les Ingénieurs. Le Professorat aussi est une profession libérale, toutefois le second alinéa dit que l\'on peut en restreindre l\'exercice par une loi spéciale, c\'est ce qui a lieu pour l\'exercice de l\'avocatie et de la médecine, etc. Je peux bien accepter la suppression des mots « dans les limites de la Constitution fédérale », mais je n\'en vois pas l\'importance. Si l\'on a ajouté ces mots, c\'est parce qu\'il existe dans la loi fédérale un texte spécial concernant cette liberté. M. H. de Torrente. II n\'est pas indiffèrent de mettre ou de ne pas mettre cette phrase « dans les limites de la Constitution fédérale », parce que la Constitution fédérale ne dit rien ä ce sujet et que nous nous interdisons de limiter ainsi la liberté des professions libérales. Cet article dit le libre exercice des pro- — 37 — fessions libérales et la liberté du commerce et de l\'industrie sont garantis. Mais il y a une différence essentielle entre la liberté de commerce et l\'exercice des professions libérales, car la loi ne s\'occupe jamais de l\'individu, mais de la marchandise et du genre de commerce, tandis que le libre exercice d\'une profession libérale dépend de la personne. M. Couchepin appuie la proposition de M. de Torrente, car il est arrivé à la conviction qu\'il est préférable de restreindre le second alinéa à la première partie et supprimer les derniers mots. II n\'est pas juste d\'assimiler le libre exercice du commerce avec le libre exercice des professions libérales, car il n\'y a pas de restriction pour l\'exercice du commerce. Je crois qu\'il est préférable de se • contenter de dire « l’exercice de ce droit est règle par la loi », sans parier des limites de la Constitution fédérale. M. Bioley. J\'estime que la loi doit être conforme à la réalité des faits, eh bien, les termes de cet article ne répondent pas à la réalité des faits. L\'alinéa premier parle du droit de libre Etablissement et de libre exercice des professions libérales et l\'alinéa suivant dit que cette liberté est limitée par la loi. II vaudrait donc mieux dire : « Le droit d\'établissement, au lieu de dire « le droit de libre établissement »; f l\'exercice des professions libérales au lieu de «le libre exercice» des professions libérales. Je propose donc de bitter le mot libre avant établissement ainsi que le même qualificatif donne ä l\'exercice des professions libérales. Au vote la proposition de suppression de M. de Torrente est adoptée.', '1907-02-25', 10);
            INSERT INTO debat VALUES (5, 'L\'Etat encourage et subventionne dans la mesure de ses ressources financières : 1° L\'agriculture, l\'industrie, le commerce et en général toutes les branches de l\'économie publique intéressant le Canton ; 2° L\'enseignement professionnel concernant le commerce, l\'industrie, l\'agriculture et les arts et métiers ; 3° L\'élevage du bétail, l\'industrie laitière, la viticulture, l\'arboriculture, l\'économie alpestre, l\'amélioration du sol, la sylviculture et les syndicats agricoles ou professionnels.» M. Pélissier propose de sup primer l\'énumération qui est faite au chiffre 3 qui a plutôt un sens restrictif et est superflu et peut rentrer sous chiffre 2. II se peut que par suite du développement qui va immanquablement s\'accentuer dans le pays on puisse favoriser une autre partie que Celles qui sont ici 6numerees ; je propose de mettre « toutes les branches de l\'agriculture. » M. Raym. Evéquoz fait observer que l\'alinéa 2 ne prévoit que l\'enseignement, tandis que le troisième alinéa concerne les différentes branches de l\'agriculture qui sont subventionnées. On pourrait plutôt supprimer le premier alinéa, mais je propose de conserver le texte tel qu\'il a été vote en premiers débats. M. Maurice Troillet, rapporteur, donne lecture in parte qua des délibérations qui ont eu lieu en première lecture pour démontrer qu\'il est bien entendu que cette énumération n\'a rien de restrictif, mais qu\'elle n\'est faite qu\'ä titre d\'exemple. M. Ed. Delacoste propose d\'intervertir l\'ordre de ces paragraphes et de mettre le paragraphe 3 avant le paragraphe 2. M. Pellissier propose la suppression du 3 me paragraphe et d\'ajouter au premier : < ainsi que les syndicats agricoles ou professionnels ». M. Bioley, conseiller d\'Etat, estime aussi qu\'il est préférable de supprimer cette énumération et déclarer accepter la proposition de M. Pellissier. M. Georges Morand abonde dans le sens de la proposition de M. Pellissier et propose aussi de biffer l\'alinéa 3, car le mot agriculture dans le premier paragraphe comprend tout, même les syndicats agricoles. M. Evéquoz recommande encore le maintien du paragraphe 3 afin que les populations voient que nous avons pensé à toutes ces branches, et il n\'y a absolument aucun mal à ce qu\'on le laisse. On fait une différence entre l\'agriculture et le commerce, c\'est pourquoi il est dit d\'une manière générale au premier paragraphe que l\'Etat encourage et subventionne l\'agriculture, l\'industrie et le commerce, mais l\'Etat subventionne d\'une manière spéciale l’agriculture ; il donne des subventions aux propriétaires de bétail, tandis qu\'il ne subventionne pas le commerçant, mais il favorise le commerce en général. Je prie donc la Haute Assemblée de maintenir cette énumération. M. Ev6quoz fait encore observer que dans le projet du Conseil d\'Etat cet article comprenait 3 articles qui après discussion ont été réunis en cet article unique. M. Bioley intervient encore pour proposer la suppression de cette énumération qui pour- — 41 — rait être plutôt nuisible qu\'utile ä l\'agriculture, en ce sens qu\'on pourrait l\'interpréter comme ayant un caractère limitatif. M. Maurice Troillet fait alors la proposition de mentionner au protocole que cette énumération n\'a den de restrictif. M. Pellissier répond qu\'une mention au protocole n\'a pas une bien grande importance. II y a des branches qui ne figurent pas dans cette énumération, telles que l\'apiculture, la pisciculture, etc. M. Bressoud propose aussi le maintien de cette disposition qui a été adoptée sur la proposition des représentants de l\'agriculture. M. G. Morand propose encore la suppression dans l\'intérêt de l\'agriculture en général pour l\'avenir. M. Raoul de Riedmatten est parfaitement de l\'avis qu\'une mention au protocole n\'ait pas sa raison d\'être à l\'occasion de la Constitution et il propose le maintien du troisième paragraphe. Cet article dit au commencement que l\'Etat encourage et subventionne dans la mesure de ses ressources financières et le troisième alinéa spécifie les branches que Ton veut favoriser, car on ne peut pas absolument subventionner toutes les branches. L\'orateur propose le renvoi ä la Commission. Cette proposition de renvoi est rejetée. Au vote l\'article 14 est adopte tel qu\'en Premiers débats ä une grande majorité et l\'insertion au protocole votée, ä savoir que cette Enumération n\'a rien de restrictif mais est faite a titre d\'exemple.', '1907-02-25', 14);
            INSERT INTO debat VALUES (6, 'L\'Etat doit favoriser et subventionner 1\'etablissement d\'hôpitaux, de cliniques et d\'infirmeries de district ou d\'arrondissement. « II peut aussi créer un établissement signataire cantonal. » La Commission propose d\'ajouter un troisième alinéa congru comme suit : « Dans des cas exceptionnels, l\'Etat peut aussi subventionner 1\'etablissement d\'infirmeries de commune.» La Commission motive sa proposition sur l\'éventualité à prévoir, qu\'une commune veuille une infirmerie, mais que le district n\'en veuille rien. Cette Subvention pourrait être donnée sous la condition que si plus tard le district voulait aussi y participer, il pourrait le faire. M, Burgener. Le Conseil d\'Etat en examinant la nouvelle proposition de la Commission s\'est dit que par l\'introduction des art. 14 et suivants, il avait déjà suffisamment marque son intention et sa volonté de s\'intéresser d\'une manière plus active que par la passe au développement des problèmes économiques et des œuvres philanthropiques du Canton. A l\'endroit de l\'article que nous discutons, le Conseil d\'Etat avait proposé une formule moins impérative que celle admise en premiers débats. Le projet disait que l\'Etat pourra fonder une clinique chirurgicale cantonale et favoriser l\'établissement d\'hôpitaux et d\'infirmeries de district ou d\'arrondissement. Lors des premiers débats, la Haute Assemblée a donné au Conseil d\'Etat un mandat impératif en statuant qu\'il devra favoriser et subventionner ces établissements. C\'était là un bon pas en avant. Mais aujourd\'hui la Commission veut encore aller plus loin et vous recommande l\'adjonction d\'un nouvel alinéa ainsi conçu : « Dans les cas exceptionnels, l\'Etat peut aussi subventionner l\'établissement d\'infirmeries de commune. » Le Conseil d\'Etat reconnait les bonnes intentions de la Commission quand elle vous propose l\'adjonction de cet alinéa ; il reconnait qu\'il importe de venir en aide le plus possible ä nos institutions philanthropiques, d\'encourager les communes dans ce domaine ; mais il y a une limite ä tout. N\'allons pas trop loin. N\'éparpillons pas trop nos forces et nos ressources ! Dans ce domaine il ne sait pas trop décentraliser, mais plutôt unir les forces. Si l\'Etat s\'engage ä subventionner les hôpitaux de commune, il ne pourra venir efficacement en aide aux hôpitaux d\'arrondissement. Les ressources ne suffiront pas. Malgré ces scrupules et ces objections, la majorité du Conseil d\'Etat ne veut pas combattre cette adjonction espérant qu\'elle ne vise que des cas exceptionnels, tout en rendant la Haute Assemblée, d\'un autre côté, attentive sur les conséquences qu\'entrainerait une trop large application de cette disposions. Une minorité du Conseil d\'Etat propose d\'écarter purement et simplement l\'alinéa propose par la Commission, comme entrainant de grosses conséquences pour la Caisse de l\'Etat. M. Raph. de Werra, comme chef du Département des Finances, combat cette adjonction ; étendre davantage ces subventions serait créer une source de dépenses exagérées. L\'orateur donne ici un aperçu de la Situation nuancier de l\'Etat. En présence d\'un déficit pareil, ajoute-t-il, je crois qu\'il est temps de s\'arrêter un petit moment ; il ne sait pas toujours accumuler les déficits, car ces déficits, tôt ou tard doivent être couverts. Par ces considérations je vous prie de bien vouloir observer une sage réserve dans les dépenses de l\'Etat et vous propose de biffer l\'adjonction proposée par la Commission. M. Evéquoz, au nom de la minorité de la Commission, combat la proposition de la majorité, par le motif que si celle-ci devait être adoptée elle aurait pour essai de disséminer les forces et les ressources financières au détriment des hôpitaux de district et de l\'hôpital cantonal éventuellement, établissements devenus nécessaires et qui doivent être construits et aménagées avec toutes les exigences et conforts modernes. De plus le subventionnement des infirmeries de commune ne profiterait qu\'aux grandes communes qui laisseraient les petites communes de la montagne dans l\'isolement, et celles-ci seraient prétéritées. Herr Hermann 8 ei (er spricht für den Antrag der Kommission, welche llon dem Standpunkt ausging, daß man der Verwirklichung des in die Verfassung aufgenommenen Gedankens auf Errichtung von Bezirksoder Kreisspitalern nachhelfen müsse; es könnte nun vorkommen, daß sich ein Bezirk ans Selbstsucht und Regionalgeist der Gemeinden für einen gemeinsamen Spitalbau nicht einigen könnte; wenn aber eine größere Gemeinde in diesem Bezirke sei, die auf einen Spitalbau dringe, so solle der Staat den Geist der Philan tropie nicht ersterben lassen, sondern auch die Gemeinden unter gewissen Bedingungen subventionnieren können, M. Maur. Trollet, rapporteur de la Commission. Les nombreux discours qui viennent d\'être prononces vous font voir l\'importance de cette disposition pour les finances de l\'Etat, mais seulement il n\'a pas été dans l\'idée de la Commission de subventionner toutes les communes pour faire des infirmeries. La Commission a seulement voulu prévoir le cas où le district ne pourrait pas s\'entendre pour créer une infirmerie, une commune puisse profiter de cette Subvention ; ce ne serait donc seulement dans le cas où il n\'y aurait pas entente en district. Pour éviter tout malentendu je propose le renvoi ä la Commission pour meilleure rédaction. Au vote le renvoi ä la Commission est rejeté. L\'article est vote tel qu\'en premiers débats. Les débats sur la Constitution sont ici suspendus. II est donne connaissance d\'une demande d\'interpellation de M. le depute Jacques de Riedmatten relativement aux mesures que l\'Etat compte prendre concernant la protection ä donner à l\'arboriculture contre les ravages occasionnes par les lièvres. Cette Interpellation sera mise à l\'ordre du jour d\'une prochaine séance. II est décidé que pendant cette Session les séances s\'ouvriront ä 9 heures du matin. La séance est levée ä midi trois quarts et renvoyée au lendemain avec l\'ordre du jour suivant : Loi sur l\'assurance du détail. Révision de la Constitution.', '1907-02-25', 18);
            INSERT INTO debat VALUES (7, 'Sur demande écrite du quart des propriétaires de bétail bovin d\'un cercle d\'inspection, le Conseil communal est tenu de procéder sans retard : à l\'établissement de la liste de tous les propriétaires de bétail bovin ayant domicile dans le cercle d’inspection ; à la convocation de ces propriétaires, par la voie du Bulletin officiel et par Publication aux criées ordinaires deux dimanches consécutifs, à une assemblée qui devra se prononcer sur la création d\'une caisse d\'assurance. La convocation devra mentionner le jour, l’heure, le lieu de la réunion et l’objet à l’ordre du jour ainsi que l’avertissement que les propriétaires présents peuvent à eux seuls décider valablement la création de la caisse d\'assurance. » A la litt, bj sur la proposition de la Commission la Publication au Bulletin officiel est supprimée, attendu que la convocation aux criées publique » deux dimanches consécutifs est suffisante et que les frais d\'insertion dans la feuille officielle sont des dépenses superflues.', '1907-02-26', 3);
            INSERT INTO debat VALUES (8, 'Le Président de la commune dirige l’assemblée ; il est assisté du secrétaire du Conseil qui tient le protocole. » L\'Assemblée ajoute ä cet article un alinéa propose par la Commission et conçu comme suit : « Dans les communes formant plusieurs Cercles d\'inspection, le Conseil communal désignera les présidents et les secrétaires des différents Cercles. »', '1907-02-26', 4);
            INSERT INTO debat VALUES (9, 'Après délibération, l’assemblée se prononce à la majorité absolue des propriétaires de bétail bovin présents ou représentes, sur l\'établissement de la caisse d\'assurance. « En cas de vote affirmatif, l’assemblée nomme, séance tenante, une Commission de 3 ä 5 membres, chargée d\'élaborer les Statuts. « Les décisions prises sont rendues publiques par voie des criées ordinaires le dimanche qui suit le jour de l\'assemblée. » Le mot « dûment • » est ajoute devant le mot « représentes ».', '1907-02-26', 5);
            INSERT INTO debat VALUES (10, 'La Commission chargée d\'élaborer les Statuts est tenue de convoquer dans le terme d\'un mois, dès l\'expiration du délai de recours prévu ä l\'art. 7, une nouvelle assemblée des propriétaires Intéresses, en vue de l\'adoption des Statuts et de la nomination des organes de l\'assurance. « Les décisions de cette assemblée sont également prises à la majorité absolue des membres présents ou dûment représentes. » Sur la proposition de la Commission les mots « des organes de l’assurance » sont remplacés par les suivants « du Comite, de la Commission de taxe et des vérificateurs des comptes ».', '1907-02-26', 9);
            INSERT INTO debat VALUES (11, 'L\'assemblée générale se compose des propriétaires de bétail bovin présents ou dûment représentes. La rare personne ne peut se charger de plus d\'une procuration. « L\'assemblée est convoquée :  en réunion ordinaire, une fois dans l\'année, soit dans le courant de février ; b) en assemblée extraordinaire, lorsque la demande en est faite par écrit au Comité par le quart des propriétaires du Cercle d\'assurance, ou lorsque le Comite le trouve Nécessaire. La demande doit indiquer le but de la convocation. Gallaeci se fera conformément aux prescriptions des Statuts. « Dans les votations, chaque propriétaire dispose d\'une voix. » M. Bioley, chef du Département de l\'Intérieur, fait observer, au point de vue rédactionnel du premier alinéa, qu\'il vaut mieux remplacer les mots «la mère personne ne peut se. Charger de plus d\'une procuration » par «la même personne ne peut être charge de plus d\'une procuration ». La phrase telle qu\'elle a été adoptée en Premiers débats renferme une idée d\'initiative de la part de la personne, tandis qu\'elle n\'est pas l\'idée que nous voulons exprimer', '1907-02-26', 13);
            INSERT INTO debat VALUES (12, 'L\'assemblée générale a les attributions suivantes : a) élection des membres du Comité, de la Commission de taxe, des vérificateurs des comptes et des suppléants ; b) l’approbation ou rejet des comptes de Ia caisse ; cl adoption et révision des Statuts ; d) l’exclusion des membres ; el fixation de Ia prime annuelle ou cotisation. « Toutes les décisions de l\'assemblée générale sont prises ä la majorité absolue des membres présents ou dûment représentes. » La Commission propose la suppression de la littera dj « exclusion des membres » car cela est en contradiction avec l’article 8 qui statue que l\'assurance est obligatoire pour tous les propriétaires de bétail bovin du Cercle d\'inspection. M. Bioley, chef du Département de l\'Intérieur, se déclaré d\'accord de bisser le texte de la littera dl, et de le remplacer par « prononce des amendes >. M. Pellissier préfèrerait les mots « Sanction des pénalités ». M. H. Seiler attire l\'attention de la Haute Assemblée sur l\'importance de cette suppression ; l\'orateur a eu l\'occasion de s\'entretenir ä ce sujet avec un personnage très expérimente, un vétérinaire vaudois, qui lui a dit qu\'il lui paraissait nécessaire que Ton — 55 — puisse exclure de l\'assurance un membre qui négligerait complètement son bétail. M. Bioley, chef du Département d\'Intérieur, répond que l\'exclusion irait en contradiction directe des dispositions de l\'art. 8, qu\'il serait par trop facile d\'étudier : une personne qui ne voudrait pas entrer dans l\'assurance n\'aurait qu\'ä faire en sorte de s\'en faire exclure. L\'article est adopté avec l\'amendement « Sanction des pénalités »', '1907-02-26', 14);
            INSERT INTO debat VALUES (13, 'Le Comité est nommé pour la durée de 4 ans ; il est rééligible ; il se compose de trois membres au moins, dont un président, un vice-président et un secrétaire. « Le Comite gère les affaires de la caisse selon les prescriptions de la loi, du règlement d\'exécution et des Statuts ; il nomme le caissier et veille à l’exécution des décisions de l\'assemblée générale. « Le caissier est tenu de fournir un cautionnement suffisant. II doit placer les fonds disponibles ä la Caisse hypothécaire et d\'épargne du Canton ou dans ses agences. » — 56 — La Commission estime qu\'il est indispensable que l\'inspecteur du détail fasse Partie du Comite conformément au désir formel exprime par le Département fédéral de l\'Agriculture et propose d\'ajouter : « L\'inspecteur du détail fait partie de droit du Comite.» Lorsque l\'inspecteur sera capable d\'être secrétaire on le nommera, sinon on en nommera un autre. II va de soi que partout où il sera capable, il sera la cheville ouvrière de la caisse d\'assurance. M. Pignat fait observer qu\'il peut y avoir deux inspecteurs de bétail ; il faudrait alors mettre : « Tous les inspecteurs de bétail devront faire partie du comité ». M. Jacques de Riedmatten, rapporteur de la Commission, reconnait que cette proposition a du bon, mais s\'il y a trois inspecteurs du bétail et qu\'ils fassent partie de droit du comité, seront-ils capables de gérer la caisse ? M. J. Arlettaz. Je crois que les motifs qui ont fait écarter l\'inspecteur de bétail comme faisant partie de droit du comité, en premiers débats, subsistent et je propose de maintenir l\'article tel qu\'il est ; un Inspecteur de bétail peut être tout ä fait ä la hauteur comme tel, mais ne sera pas capable de gérer la caisse d\'assurance. M. J. de Riedmatten. Je crois que si nous ne voulons pas nous exposer ä un échec vis-à-vis de Berne, nous devons décider que l\'inspecteur du bétail fera partie de droit du Comite. M. Bioley, chef du Département de l\'Intérieur, accepte cette proposition. Au vote, l\'article 11 est adopte avec l\'adjonction : « L\'inspecteur du détail fait partie de droit du Comite. »', '1907-02-26', 19);
            INSERT INTO debat VALUES (14, 'La Commission de taxe se compose de 3 membres et de 2 suppléants; elle est chargée de l\'estimation des animaux soumis ä l\'assurance. Le président du Comité ne peut faire partie de cette Commission. » La Commission propose une modification dans ce sens que «les membres du Comité ne peuvent faire partie de la Commission de taxe ».', '1907-02-26', 21);
            INSERT INTO debat VALUES (15, 'L\'assurance du bétail de commerce n\'est pas obligatoire. » La Commission propose un changement en ce sens que l\'assurance du bétail des commerçants payant patente n\'est pas obligatoire, cela en vue d\'éviter des inconvénients. On ne peut, en effet, forcer un commerçant de bétail qui acheté 30 à 40 vaches à s\'assurer, mais par contre nous ne voulons pas, dit le rapporteur, qu\'un petit propriétaire qui acheté une pièce de bétail, par exemple, puisse venir dire : « Je fais du commerce et je ne suis pas oblige de m\'assurer. » M. Catherine rend la Haute Assemblée attentive sur la signification de ce texte. Vous dites « l’assurance n\'est pas obligatoire », mais pour qui n\'est-elle pas obligatoire ? Est-ce pour le Comite que cette assurance n\'est pas obligatoire ou bien est-ce pour le propriétaire ? M. J. de Riedmatten. Le Comité d\'assurance peut accepter ou refuser certaines personnes, voilà l\'idée de la Commission. M. Pellissier fait observer qu\'il faudrait chercher une autre rédaction, car pour éluder la loi il pourrait se faire que d\'aucuns paient la patente. M. H. de Torrent fait ressortir qu\'il y a équivoque et que pour en sortir il faudrait préciser que l\'assurance n\'est pas obligatoire ni pour le propriétaire ni pour le Comité, sauf renvoi ä la Commission pour meilleure rédaction. M. Bioley, chef du Département de l\'Intérieur, accepte le renvoi ä la Commission, — 59 — tant au point de vue contenu dans cet article qu\'au point de vue de la rédaction. La discussion est close et l\'article renvoyé à la Commission.', '1907-02-26', 25);
            INSERT INTO debat VALUES (16, 'Tout animal est, à partir de sa taxe, admis ä l\'assurance. c En cas de multiples assurances, le préjudice subi par l\'assure sera Supporte proportionnellement par les diverses caisses engagées.» La Commission propose une modification au second alinéa et de substituer au texte existant in parte qua « seront indemnises suivant les ordonnances du règlement d\'exécution ». M. Bioley, chef du Département de l\'Intérieur, se déclaré d\'accord avec la modification proposée par la Commission qui termine d\'une manière heureuse les débats qui ont surgi en première lecture, mais il préfèrerait toutefois une autre rédaction disant : « suivant les prescriptions du règlement d\'exécution ». M. J. de Riedmatten, rapporteur de la Commission^ au nom de cette dernière, déclaré accepter cette proposition. Au vote, l\'article 26 est vote conformément ä la proposition de la Commission, amendée par M. Bioley.', '1907-02-26', 26);
            INSERT INTO debat VALUES (17, 'Sion est le chef-lieu du Canton et le siège du Grand Conseil, du Conseil d\'Etat • et du Tribunal cantonal. Ces Corps peuvent toutefois siéger ailleurs si des circonstances graves l\'exigent. « Le décret du 1er Décembre 1882 détermine les prestations du chef-lieu. « Lors de la création d\'établissements cantonaux on doit tenir compte des diverses parties du Canton. « La commune qui devient le siège d\'un établissement cantonal peut être tenue à des prestations. » La Commission rappelle ä la Haute Assembler qu\'après une discussion assez longue de cet article, M. Alex. Seiler a proposé une Insertion au protocole prévoyant que l\'art. 26 doit être interprète dans ce sens que lorsque la construction ou la fourniture de locaux plus vastes deviendront nécessaires pour le Grand Conseil et le Tribunal cantonal, la ville de Sion devra supporter de nouvelles prestations. La Commission propose de voter ä nouveau cette Insertion au protocole. M. IL Seiler voit des inconvénients dans l\'adoption du dernier alinéa de cet article. Le Grand Conseil pourrait imposer un établissement cantonal à une commune qui alors en vertu de cette disposition devra fournir des prestations. Cela pourrait être à Charge à une commune, c\'est pourquoi on propose la suppression de cet alinéa. II y aura, au reste, lors de créations de ce genre, chaque fois un décret et on pourra préciser dans ce décret les prestations des communes. M. Loretan demande quelle valeur ont les inscriptions au protocole, car ä son avis elles n\'en ont absolument aucune ; elles n\'ont de valeur qu\'autant qu\'elles sont liées avec le texte sous forme d\'interprétation. Si vous décidez que ces insertions soient aussi soumises au peuple lors de la votation, alors ces insertions peuvent avoir leur valeur, mais autrement elles n\'en ont point. M. Raym. Evequoz se déclare t’ont surpris de la question qu’avec M. Loretan qui était président de la Commission lors des Premiers débats ; je pourrais donc aussi lui poser la même question. Ces insertions au protocole sont des sources d’interprétation ; elles expliquent le texte qui a été vote ; c\'est une indication pour les assemblées futures sur la manière dont nous entendions l’interpréter ; c\'est pour cela que ces insertions peuvent être très utiles. Aux Chambres fédérales ces insertions se présentent assez fréquemment. Répondant à M. Hermann Seiler il fait observer que la disposition dont il demande la suppression n\'émane pas de la Commission des premiers débats ; c\'est un membre de la Haute Assemblée qui a fait cette proposition qui a été adoptée ä l\'unanimité. Je crois bien que cette disposition n\'a pas grande importance, parce que le Grand Conseil pourra toujours décider par décret les conditions de ces établissements, mais je ne crois pas que le Grand Conseil puisse imposer une Obligation ä une commune. Je ne vois cependant pas pourquoi on supprimerait cette disposition ; c\'est une question de principe général ; le principe en soi est juste et on peut l\'insérer dans la Constitution. M. Loretan repond qu\'il a toujours combattu les insertions au protocole lorsqu\'il était Président de la Commission. M. Burgener, président du Conseil d\'Etat. Après l\'expose du président de la Commission j\'aurais pu nie dispenser de prendre la parole ; je tiens cependant ä vous communiquer l\'idée du Conseil d\'Etat ä l\'endroit de l\'art. 26. Bien que les mentions au protocole — 77 — puissent avoir en règle générale plutôt un caractère platonique, elles n\'en ont pas moins souvent leur valeur ä raison du rôle interprétatif qu\'elles sont appelées ä jouer. En l\'espèce, 1a mention au protocole, proposée par la Commission a certainement sa raison d\'être et le Conseil d\'Etat l\'appui. En effet, il y a deux dispositions bien distinctes ä retenir dans cet article par rapport au chef-lieu du Canton. On y mentionne le décret du 1er Décembre 1882 pour affirmer ä nouveau que Sion en raison de son choix comme Capitale du Canton, doit fournir les locaux pour le Grand Conseil et la Cour d\'Appel. Pour le moment, nous voulons bien admettre que Sion a satisfait aux exigences du dit décret. Mais, qui ? si notamment la salle du Grand Conseil par suite de l\'augmentation du nombre des députés devenait trop petite ? II sera alors, et la git l\'importance de la mention au protocole, bien entendu que Sion devra fournir des locaux plus vastes et plus spacieux ä ses frais, quitte ä examiner la question de savoir, si la Capitale ne pouvait pas être indemnisée dans une certaine mesure, lorsque notamment les prestations nouvelles seraient sensiblement plus lourdes que Celles déterminées dans le décret de 1882. Sion peut en second lieu être vise par le dernier alinéa de l’Article. S\'il devient le siège d\'un nouvel établissement cantonal, il pourra être tenu à de nouvelles prestations en dehors de celles auxquelles nous avons fait allusion tout à l’heure. Voilà les deux considérations qu\'ü y a Heu de retenir et qui motivent la mention au protocole. Pour ce qui concerne la proposition de M. H. Seiler, de supprimer le dernier alinéa, je ne pourrais non plus y donner la main et cela pour les motifs qu\'a fait valoir M. le Président de la Commission. Rares seront les communes auxquelles on imposera certains établissements ; au contraire, nombreuses seront parfois les localités qui convoitent le siège de tel ou telle Institution publique. 11 sera dès lors tout naturel, que les communes qui retirent des avantages de ce fait fournissent certaines prestations. Je vous propose donc de maintenir le dernier alinéa. M. H. Seiler déclare n\'avoir pas combattu le texte dans ce sens, que les communes qui deviennent le siège d\'un établissement cantonal ne puissent pas être tenues ä des prestations. Je n\'ai pas dit que Ton ne pourra exiger des prestations. Au vu des assurances données par M. le President du Conseil d\'Etat, il retire sa proposition. La discussion est dose et l\'art. 26 est vote — 79 — tel qu\'en premiers débats, mais il sera insère au protocole que cet article doit être interprète dans ce sens que lorsque la construction ou la fourniture de locaux plus vastes deviendront nécessaires pour le Grand Conseil et le Tribunal cantonal, la ville de Sion devra supporter de nouvelles prestations.', '1907-02-27', 26);
            INSERT INTO debat VALUES (18, 'Sont Valaisans 
             1. Les bourgeois par droit de naissance d\'une commune du Canton ;
            2. Ceux à qui la naturalisation a été conférée par la loi ou le Grand Conseil.
             Lorsque la naturalisation est conférée par le Grand Conseil, le postulant doit, pour que  sa demande puisse être prise en considération, produire une déclaration constatant qu\'un  droit de bourgeoisie lui est assuré dans une  commune du Canton et remplir les autres  conditions fixées par la loi sur la naturalisation. 
             « Nul étranger au Canton ne peut acquérir   un droit de bourgeoisie sans avoir été préalablement naturalisé par le Grand Conseil.
             « La législation fédérale prévue à l\'art. 44 de la Constitution fédérale est réservée. » 
            M. Léon Martin combat cette disposition. Il estime qu\'il y a danger de faire de cette  règle actuellement existante une disposition constitutionnelle, car se serait restreindre considérablement la liberté du Grand Conseil en vue de la législation future. Il est pos sible, croit-il, que dans un avenir qui pourrait n\'être pas éloigné, des circonstances spécial  les engagent le Grand Conseil à réformer la loi sur la naturalisation. Le Valais, par le   percement du Simplon, voit et verra toujours davantage les étrangers affluer et se fixer en  grand nombre sur son territoire. Il y aura  lieu de faciliter, à l\'exemple des cantons frontières (Genève, Neuchâtel, Bâle, etc.), l\'accès  à la naturalisation de ces étrangers. Peut-être pourra-t-on décider qu\'il suffira dans ce cas  d\'un simple indigénat, et par le fait même  l\'obligation d\'acquérir une bourgeoisie tombera, C\'est pourquoi il propose la suppres sion pure et simple de la disposition ci-dessus  mentionnée, figurant à l\'art. 27. 
            M. Couchepin, chef du département de Justice et Police, informe l\'Assemblée que le  Conseil d\'Etat s\'est prononcé en faveur du  maintien de cette disposition qui n\'empê che pas que l\'obtention de la naturalisation  soit facilitée par une loi nouvelle. Par contre il y aurait peut-être un certain danger à ne  pas fixer dans la Constitution que le droit de  cité dans une bourgeoisie du Canton est nécessaire pour être naturalisé Valaisan.
            Personnellement il est partisan d\'une modification de la loi sur la naturalisation dans.  Le sens d\'un allègement des conditions d\'admission au droit de cité cantonal. Peut-être y aurait-il lieu de créer deux classes de bour geois: les jouissants et les non-jouissants. 
            M. Défayes appuye la proposition Martin qu\'il avait déjà présentée lui-même aux pre miers débats.
            M. H. de Torrenté expose que si cette dis position a été insérée dans le projet c\'est que  des difficultés avaient surgi ces années der nières, des étrangers admis au droit de bour geoisie ayant prétendu se passer de la naturalisation.
            Peut-être bien l\'accession à la bourgeoisie  devra-t-elle être facilitée, mais non pas en créant deux catégories de citoyens, ce qui  serait absolument contraire à la direction donnée par l\'histoire de la législation suisse qui, depuis 1848, s\'efforce de supprimer ces distinctions.
            Il ne faut pas oublier non plus que la naturalisation n\'est pas toujours conférée par le Grand Conseil, elle peut l\'être aussi par la loi. Cette faculté sera donc toujours à la disposition de l\'autorité législative.
             Après clôture de la discussion, l\'art. 27 est adopté tel qu\'en Premiers débats. ', '1907-02-27', 27);
            INSERT INTO debat VALUES (19, 'Sont soumis à la votation du  peuple :
            1.	La révision totale ou partielle de la Con stitution.
            2.	Les concordats, les conventions, les trai tés rentrant dans la compétence canto nale. 
             3. Les lois et décrets élaborés par le Grand  Conseil.
            « Sont exceptés •
            a)	Les décrets qui ont un caractère  d\'urgence ou qui ne sont pas d\'une portée générale et permanente ;
            b)	Les dispositions législatives qui ont  pour but d\'assurer l\'exécution des lois fédérales.
            4. Toute décision du Grand Conseil entrainant une dépense extraordinaire de 60,000 fr. ou, pendant le terme de trois ans, une dépense moyenne de 20,000 frs, si ces dépenses ne peuvent être couvertes par les recettes ordinaires du  budget.
             5. Toute élévation de l\'impôt sur le capital et le revenu fixé à l\'art. 23, à moins qu\'elle ne soit rendue nécessaire par les contributions extraordinaires que la Confédération peut imposer aux cantons  en vertu de l\'art. 42 de la Constitution fédérale. 
            La Commission propose d\'ajouter à la litt. 
            a) du chiffre 3, la disposition suivante : 
            « Dans chaque cas particulier cette exception doit faire l\'objet d\'une décision spéciale  et motivée. » ', '1907-02-27', 29);
            INSERT INTO debat VALUES (20, 'Lorsqu\'une demande d\'initiative doit entraîner de nouvelles dépenses, qui ne peuvent pas être couvertes par les recettes ordinaires de l\'Etat, ou supprimer   des ressources existantes, le Grand Conseil doit soumettre en même temps au peuple  des propositions touchant les recettes nouvelles à créer. »
            M. Defayes. Cet article est en soi une sage disposition ; il a pour but d\'empêcher que, par voie d\'initiative, le peuple vote des dé penses qui ne peuvent être couvertes par  les recettes ordinaires de l\'Etat ou supprime  des ressources existantes, mais d\'un autre côté cet article tel qu\'il est rédigé. a pour conséquence de réduire, dans de notables  proportions, l\'exercice du droit d\'initiative. Soit le Grand Conseil, soit le Gouvernement  pourront mettre en avant le fantôme de l\'augmentation du taux de l\'impôt et alors le peuple se passera de ce droit d\'initiative. L\'art. 31 dit que si le Grand Conseil n\'approuve pas l\'initiative, celle-ci est soumise à l\'adop tion ou au rejet du peuple, et le Grand Conseil peut motiver sa décision devant le peuple. Il suffira alors au Grand Conseil de dire.  Cette initiative emporte pour nous telles conséquences, si elle est votée nous devrons avoir recours à telles mesures ; et le peuple  sera assez sage pour dire qu\'il ne veut pas s\'embarquer dans de telles conditions. Je  crois donc que cette disposition ne servira  qu\'à enrayer l\'initiative populaire et je propose la suppression de cet article. ', '1907-02-27', 32);
            INSERT INTO debat VALUES (21, 'La minorité de la Commission propose de remplacer le texte de l\'article par le suivant •
             « Le Conseil d\'Etat est nommé par le peuple en un arrondissement cantonal, tous les quatre ans, en même temps que les députés  au Grand Conseil, de manière que deux d\'en tre eux soient choisis dans la partie du Can ton qui comprend les districts actuels de Conches, Brigue, Viège, Rarogne, Loèche et  Sierre, un dans celle des districts de Sion, Hérens et Conthey, et deux dans celle des  districts de Martigny, Entremont, St-Maurice et Monthey. »', '1907-02-27', 42);
            INSERT INTO debat VALUES (22, 'Le Grand Conseil a les attributions suivantes :
            1.	Il vérifie les pouvoirs de ses membres   et prononce sur la validité de leur élec tion 
            2.	Il accepte, amende ou rejette les pro jets de loi ou de décret présentés par le Conseil d\'Etat. En cas d\'initiative populaire, il procède conformément à ce qui est dit aux art. 31 et 33 ; 
            3.	Il exerce le droit d\'amnistie, le droit  de grâce et de commutation de peine ;  4. Il accorde la naturalisation 
            5.	Il examine la gestion du Conseil d\'Etat et délibère sur son approbation ;
            Il peut en tout temps demander compte au pouvoir exécutif d\'un acte de son administration • 
            6.	Il fixe le budget, examine et arrête les  comptes de l\'Etat, ainsi que l\'inventaire de la fortune publique ;
            Le budget et les comptes sont rendus publics ; le règlement fixe le mode  de publication ; 
            7.	Il nomme aux dignités et bénéfices ecclésiastiques dont la repourvue appartient à l\'Etat ;
            8.	II nomme, à chaque session de Mai, le  président et le vice-président du conseil d\'Etat, le président et le vice-président du Tribunal cantonal •
            9.	Il nomme, tous les trois ans, à la ses sion de Mai, les députés au Conseil des  Etats 
             10. Il conclut les traités avec les cantons  et avec les Etats étrangers, dans les  limites de la Constitution fédérale, sauf  ratification par le peuple •  il. Il accorde les concessions de mines ; 12. Il fixe le traitement des fonctionnaires publics et alloue la somme nécessaire pour celui des employés de l\'Etat • 
            13.	I.I autorise l\'acquisition d\'immeubles, l\'aliénation ou l\'hypothèque des propriétés nationales et les emprunts pour le compte de l\'Etat ;
            14.	Il exerce la souveraineté en tout ce que  la Constitution ne réserve pas au peuple ou n\'attribue pas à un autre pouvoir ;
             15. Il exerce les droits réservés au Can ton par les art. 86, 89 et 93 de la Con stitution fédérale. »
                La Commission propose •	 
            Sous chiffre 2 de remplacer les mots : « il  accepte, amende ou rejette les projets, etc.  par les mots « il délibère sur les, etc, »
            Sous chiffre 7 de supprimer les mots et bénéfices et d\'insérer au protocole que par la disposition contenue sous ce chiffre, le Grand Conseil renonce à la nomination des  curés de Port-Valais, Vionnaz et Collombey. 
            M. P.-M. Zen-Ruffinen propose de supprimer purement et simplement la disposition sous chiffre 7 concernant la nomination par le Grand Conseil aux dignités et bénéfices ecclésiastiques dont la repourvue appartient à l\'Etat, car si le Grand Conseil n\'est pas  qualifié pour nommer les curés, il l\'est en core moins pour nommer l\'évêque.
            M. Burgener, président du Conseil d\'Etat, dit qu\'ensuile de l\'invitation qui avait été faite au Conseil d\'Etat d\'étudier la question, ce  dernier a eu une entrevue avec Mgr Abbet.  De part et d\'autre on est tombé d\'accord, et  l\'on a estimé qu\'il y avait lieu de faire abandon   de la part du Grand Conseil à la repourvue des Cures précitées. Mais dans cette entrevue il n\'a pas été question de la nomination
             
            du chef du diocèse, le Conseil d\'Etat n\'ayant  par reçu mission de faire de proposition y  relative. Cette nomination d\'ailleurs par le Grand Conseil est un trait d\'union entre le  pouvoir temporel et le pouvoir spirituel et  donne à l\'évêque une autorité toute spéciale  par le fait qu\'il est ainsi l\'élu du peuple valaisan.
            M. Burgener fait ensuite un petit historique de la question des paroisses de Vionnaz, Port-Valais et Collombey. Ces trois paroisses sont très anciennes ; des titres de 1140 font  déjà mention de la paroisse de Vionnaz; c\'é tait naguère un prieuré dépendant de celui de Lutry (Vaud). Déjà en 1546, le droit de présentation des desservants de la paroisse  appartenait à la Diète valaisanne. Collorabey  était un fief qui fut racheté en 1570 en même  temps que le prieuré de Port-Valais par l\'Etat du Valais, et dès lors, le Grand Conseil a toujours exercé le droit de nomination des curés de ces paroisses.
            Il restera à régler entre le Conseil d\'Etat et l\'Ordinaire du diocèse certaines questions de procédure découlant de l\'abandon que nous  enregistrons aujourd\'hui.
            M. Georges Morand se félicite de voir une  fois c\'est si rare — la majorité adopter  une proposition de la minorité libérale ; mais, pour être conséquent, on devrait renoncer également à la nomination de l\'évêque. 
            M. H. Roten manifeste son étonnement de  voir se rencontrer MM, Zen-Ruffinen et G.  Morand dans une entente si parfaite, il est vraiment incroyable que tous deux fassent la même proposition. L\'orateur craint que lorsque le Grand Conseil ne pourrait plus  présenter l\'évêque de son choix au pape, ce dernier ne nous envoie un prélat étranger  au canton !
             M. H. Bioley parle en faveur du maintien de la prérogative du Grand Conseil quant à la nomination de l\'évêque et déclare qu\'au cas où l\'Assemblée serait décidée à la supprimer, il vaudrait mieux renvoyer la question au Conseil d\'Etat; car on ne doit pas prendre  une décision à la légère.
             M, C Défayes rappelle au Conseil d\'Etat  que la question n\'est pas neuve. Le Conseil d\'Etat pouvait s\'attendre à voir reprendre une proposition qui avait déjà été faite aux premiers débats ; par conséquent il devait avoir mûrement préparé sa réponse. L\'orateur fait remarquer que l\'on ne se trouve en pré sence d\'aucune convention, d\'aucun concor dat: En nommant l\'évêque, dit-il, nous ac complissons un acte unilatéral qui n\'est pas même approuvé par le pape, puisque ce der- 
            nier casse toujours l\'élection faite par le Grand  Conseil, quitte à renommer ensuite le même  titulaire. Rien n\'empêchera le Chapitre de présenter directement les candidats au SaintSiège, fussent-ils tous quatre du Haut-Valais.  En ce sens, les craintes de M. Roten de voir  un évêque étranger dans le Canton ne sont donc pas justifiées.  
            M, R, Evéquoz répond à M. Défayes : Le  Grand Conseil n\'avait pas chargé le Conseil  d\'Etat d\'étudier la question de la nomination  de l’évêque ; mais simplement celle des des servants de paroisses.
            Au vote la Haute Assemblée adopte les  changements de rédaction recommandés par la Commission sous chiffres 2 et 7 et vote  le maintien du chiffre 7 ainsi amendé par 63 voix contre 28, et ensuite l\'article sans se prononcer sur l\'insertion au protocole  proposée par la Commission.
            Les délibérations sont ici suspendues et la  séance levée à midi trois quarts et renvoyée  au lendemain avec l\'ordre du jour suivant  Révision de la Constitution.
            Interpellation de M. le député J. de Ried matten.
             Loi sur l\'assistance du bétail. 
            ', '1907-02-27', 43);
            INSERT INTO debat VALUES (23, 'Les députés doivent voter  pour le bien général, d\'après leur conviction,  sans qu\'ils puissent être liés par des instructions. »
             M. Défayes. Cet article est absolument  sans utilité pratique. On ne peut pas venir dire à un député qu\'il doit voter d\'après ses convictions, sans qu\'il puisse être lié par des instructions. C\'est un article purement décoratif et superflu, l\'orateur en demande la suppression. 
             M. Ravond Evéquoz, président de la Com mission, alors même que cet article n\'existe rait pas dans la. Constitution, les députés voteraient selon leur conscience. L\'orateur fait remarquer cependant que, si le  mandat impératif est ignoré en Valais, cette  disposition est un reste du mandat impératif  qui existait autrefois chez nous ; l\'on votait  d\'après les instructions reçues clans les cer cles de dizains, tandis que maintenant cela n\'existe plus. Il n\'est cependant pas tout à fait utile de bien faire ressortir que si nous  n\'avons pas de mandat impératif, les députés  doivent voter selon leur conscience. 
             M. Défayes constate avec plaisir que M. le président de la Commission est d\'accord avec lui pour reconnaître que cet article n\'a  pas I \'importance qu\'il avait du temps où les  conseils de dizains donnaient des instructions,  mais maintenant que ces conseils de district n\'ont plus ce droit. Cette disposition est absolument inutile. Chaque député sait ce qu\'il a à faire, il n\'est pas nécessaire de lui donner des instructions dans la Constitution.
            M. Couchepin, Conseiller d \'Etat, répond  qu\'à son avis, il est préférable de conserver cette disposition qui existe dans presque  toutes, sinon dans toutes les Constitutions de  la Suisse et qui figure dans la Constitution   fédérale à l\'art. 91 disant que les membres des deux Conseils votent sans instructions.
            La Constitution bernoise dit : les députés  sont les représentants des etc., et ne re coivent aucune instruction. 
            Je crois donc qu\'il serait préférable de con server cette disposition dans notre Constitu tion ; on pourrait peut-être supprimer la pre mière partie de l\'article, mais l\'orateur propose de maintenir Ila dernière disposition,
            Au vote la proposition de M. Défayes est  repoussée et l\'article maintenu.
            ', '1907-02-28', 46);
            INSERT INTO debat VALUES (24, 'Le mandat de député au Grand Conseil est incompatible avec les fonc  tions et les emplois dans les bureaux du Con seil d\'Etat. 
             Cette incompatibilité est aussi applicable  aux receveurs des districts et aux préposés   aux poursuites pour dettes et aux faillites. »  La Commission n\'a pas de proposition à   faire, mais pour éviter toute discussion, rap pelle que l\'art. 99 dit que la loi détermine les  autres cas d\'incompatibilité, ainsi que l\'interdiction du cumul de certaines fonctions   La Commission voulait ajouter un alinéa  à cet article, mais vu qu\'il sera bientôt éla boré une loi électorale, elle y a renoncé. 
             M, Edmond Delacoste propose de substituer à l\'expression « bureaux du Conseil d\'Etat  celle de bureaux de l\'Etat ». 
            M. Maurice Troillet, rapporteur français, dit qu\'au sein de la Commission cette modification a déjà été présentée et discutée, et  que si la Commission s\'est mise d\'accord sur le texte figurant aujourd\'hui au projet,  c\'est que l\'énonciation « bureaux de l\'Etat » ne constituait pas une simple modification de  forme, mais aussi de fond, et qu\'elle englo bait dans les fonctions rendues incompatibles celles des conservateurs des hypothèques   qui jusqu\'ici ont toujours été éligibles au Grand Conseil.
            M. Léon Martin ne voit pas d\'inconvénient  à ce que les conservateurs des hypothèques  soient rendus incompatibles et il appuye dès  lors la rectification dernandée par M. Dela coste.
            M. Burgener, président du Conseil d\'Etat,  explique que pour qu\'un fonctionnaire soit atteint par l\'incompatibilité fixé à l\'art. 48, il   faut 1 0 qu\'il soit nommé par le Conseil d\'Etat,   20 qu\'il effectue son travail dans les bureaux appartenant à l\'Etat, ou loués par celui-ci,  3 0 qu\'il soit en permanence au service de  l\'Etat. 
            Ce sont là les trois critères qui doivent  servir à déterminer si un employé de l\'Etat est incompatible ou non. Les conservateurs  des hypothèques sont conséquemment à l\'abri de cet article, dont il approuve le texte. 
            M. RIuser admet la proposition de IMO Delacoste, mais amendée par une adjonction précisant que les fonctionnaires ou employés  de l\'Etat ne sont incompatibles que s\'ils sont  nommés à ces fonctions ou à ces emplois, par le Conseil d\'Etat. Il se demande également s\'il n\'y aurait pas lieu d\'étendre l\'incompatibilité aux préfets et aux présidents  des tribunaux.
            M. Evéquoz dit qu\'avant de discuter des changements de rédaction il faut décider quelles fonctions on veut rendre incompatibles.  Jusqu\'ici seules les personnes travaillant dans les bureaux appartenant au Conseil d\'Etat ou loués par celui-ci, et par extension les forestiers d\'arrondissement qui reçoivent une   contribution de l\'Etat pour le loyer de leur  bureau, étaient incompatibles. Si l\'on veut s\'en tenir là et ne pas atteindre d\'autres em  plois, il faut maintenir l\'art. 48 tel qu\'il est  au projet. Et la Commission est de cet avis;   elle entend maintenir le statu quo, tout en  admettant la possibilité de créer plus tard  de nouvelles incompatibilités, qui seraient prévues, s\'il y échet, par la loi électorale lors  de son remaniement. 
            L\'Assemblée se prononce pour le maintien  de l\'art. 48, repoussant ainsi la proposition de  M. le député Delacoste. 
            ', '1907-02-28', 48);
            INSERT INTO debat VALUES (25, 'Cet article a la teneur sui vante : 
            « Le pouvoir exécutif et administratif est  confié à un Conseil d\'Etat composé de cinq  membres.
            « Deux d\'entre eux sont choisis dans la partie du Canton qui comprend les districts de Conches, Brigue, Viège, Rarogne, Loèche   et Sierre; un dans celle des districts de Sion, Hérens et Conthey, et deux dans celle des districts de Martigny, Entremont, St Maurice et Monthey » 
             M. Léon Martin propose de modifier le libellé du second alinéa de cet article et de dire • 
             « Deux d\'entre eux sont choisis parmi les électeurs des districts actuels de Conches, Brigue, Viège, Rarogne, Loèche et Sierre, un parmi les électeurs des districts  de Sion, Hérens et Conthey, et deux parmi  les électeurs des districts de Martigny,  Entremont, St-Maurice et Monthey. 
            Il motive sa proposition par le fait que c\'est te domicile seul qui peut donner à celui qui  est élu membre du Conseil d\'Eta.t la qualité  de représentant de telle ou telle partie du Canton. De plus, pratiquement, le domicile  politique seul peut servir de base exacte, car  on ne peut avoir qu\'un seul domicile élec toral, tandis qu\'on peut avoir simultanément   — et il est des familles se trouvant dans ce  cas — un droit de bourgeoisie dans une commune du Haut, dans une commune du  Centre et dans une commune du Bas, dans vingt ou trente communes même,
            M. Bioley, conseiller d\'Etat, partage la manière de Voir de M. Martin. Il fait remar quer à ce sujet que les Suisses d\'autres cantons, domiciliés en Valais et électeurs, peuvent être portés au siège de Conseiller d\'Etat. Ce  seul fait, dit-il, tranche la question. Prendre pour base le domicile électoral est le seul système possible. En sortant de là l\'on tombe  inévitablement dans l\'arbitraire.
            M. Evéquoz n\'est pas de cet avis; il estime au contraire que le système suivi jusqu\'ici,  celui qui prend l\'origine comme règle, est préférable, Il ne voit pas comment, et le cas peut se produire, celui qui vient de fixer son domicile, — car trois mois suffisent — sera mieux qualifié pour représenter une partie du pays au sein du Conseil d\'Etat, que celui qui en est originaire. 
            Mise au voix, la proposition de M. Martin réunit la majorité des suffrages. 
            ', '1907-02-28', 51);
            INSERT INTO debat VALUES (26, 'Le Conseil d\'Etat a les attri butions suivantes :
            1.	Il présente les projets de loi ou de dé cret ;
            2.	Il est chargé de la promulgation et de I \'exécution des lois et décrets, et prend à cet effet les arrêtés nécessaires ;
            3.	Il pourvoit à toutes les parties de l\'administration et au maintien de l\'ordre  public ;
             4. Tl dispose des forces militaires cantonales dans les limites tracées par la  Constitution et les lois fédérales ;
            Il doit immédiatement informer les députés des mesures qu\'il aura prises,  et si les circonstances l\'exigent, il con voquera le Grand Conseil.
             Ce Corps est immédiatement convoqué lorsque l\'effectif des troupes Ino bilisées dépasse celui d\'un bataillon et  lorsque le service dure plus de quatre  jours. 
            Le Conseil d \'Etat ne peut mettre sur pied que des troupes organisées par la loi.
            5.	Il entretient les rapports du canton  avec les autorités fédérales et les Etats  confédérés ;
            6.	Tl nomme, jusqu \'au grade de major in  clusivement, tous les officiers des uni tés de troupes cantonales ;
            7, Il nomine les fonctionnaires, les emplo yés et les agents dont la Constitution ou la loi n\'attribue pas la nomination à une autre autorité ;
            8. Il surveille les autorités inférieures et  donne des directions sur toutes les parties de l\'administration ; 
             9. Tl peut suspendre les autorités admnistratives qui refusent d\'exécuter ses ordres. Il doit toutefois en référer au Grand Conseil a sa première session ; 1(). Il accorde les transferts de mines. » 
             M. Alex. Seiler rend la Haute Assemblée  attentive sur l\'équivoque auquel peut prêter] \'alinéa 2 du chiffre 4 de cet article, qui prescrit que le Conseil d\'Etat doit immédiatement informer les députés des mesures qu’il aura prises et, si les circonstances l\'exi gent, il convoque le Grand Conseil. 
            Je constate un fait, dit l\'orateur, que lors  de la grève de Brigue, le Conseil d\'Etat a levé deux compagnies et que les députés n\'en ont pas été informés. Je pense qu\'à l\'avenir   il en sera de même ; il suffirait donc de dire   « il doit convoquer immédiatement le Grand Conseil si les circonstances l\'exigent, ou en texte allemand : 
            „Œt (ber ëtaatêvat) bat bett Utobett Ytat Ilitbet3ügs  eittôitberttfen, bie Ilmftünbe es erbeifd)ett\"\". NI. Burgener, président du Conseil d \'Etat,   déclare pouvoir parfaitement accepter la  proposition de M. Seiler.
            M. Défayes fait observer au sujet du no 9 prescrivant que le Conseil d\'Etat peut suspendre les autorités administratives qui refusent d\'exécuter ses ordres, que, par le vote de l\'art. 21 la Haute Assemblée a admis le principe qu \'aucun fonctionnaire ne peut être  destitué qu \'après avoir été entendu et sur décision motivée. Je voudrais adopter ici le même principe, que l\'autorité soit entendue  et que la décision soit motivée. Je ne voudrais pas que \'le Conseil d\'Etat puisse sus pendre une autorité sans au\'elle sache le motif ; c\'est pourquoi je propose l\'adjonction suivante : il peut par décision motivée et communiquée aux intéressés suspendre, etc. »
             M. Kluser. A l\'alinéa 2 du 4 il est dit:  il doit immédiatement informer les députés n, cela voudrait dire que le Conseil d\'Etat doit immédiatement envoyer une circulaire; il faudrait adopter une autre tournure, par ex.; il doit faire rapport au Grand Conseil à la prochaine session et si les circonstances I \'exigent 
            Au vote, la proposition de M, Seiler est votée, ainsi que celle de M. Défayes, avec l\'amendement Kluser. Le no 9 reçoit ainsi la rédaction suivante : Il peut, après les avoir entendus, suspendre, par décision motivée et notifiée, les autorités administratives qui refusent d\'exécuter ses ordres. II doit toutefois  en référer au Grand Conseil à sa prochaine', '1907-02-28', 52);
            INSERT INTO debat VALUES (27, 'L\'art. 21 de la loi sur la chasse autorise  l’octroi de patentes spéciales, en temps dé fendu, pour la destruction d\'animaux nuisi bles. Cette disposition pourrait vraisembla blement, par extension, permettre la chasse  aux lièvres, puisqu \'ils causent des ravages. 
             On a dit aussi que l\'introduction de hases dans le canton, sous les auspices de la Société des chasseurs, était cause de ce mal. Ce reproche est fondé dans une certaine mesure.  Aussi le Conseil d\'Etat croit-il qu \'il y a lieu   effectivement de ne plus renouveler ce repeuplement. 
            D \'autre part, on a accordé jusqu \'ici, ajoute  I \'orateur, des primes d\'encouragement pour la destruction du renard, qui est le plus grand ennemi du lièvre. Et le renard n\'est pas si nuisible qu \'on le représente généralement.  Sans doute, il s \'introduit parfois dans les poulaillers, mais d\'autre part il dévore lièvres,   mulots, hérissons, rats des champs, etc  Ces primes pourraient donc, semble-t-il, être supprimées,
            Mais je crois qu \'on s\'alarme un peu trop,  Il en est de cette question comme de la  disette de foin, qui suscitait des doléances de toutes parts. L\'Etat s\'adressa a cette  occasion à toutes les communes afin de con naître la quantité approximative de foin qu \'il  y aurait lieu de faire venir du dehors. Les cmnmandes pour tout le canton se sont élevées à 40 wagons seulement, soit 4()0,0()0 les .  -Il \'était donc pas question de disette, car que  sont 40 wagons de foin pour 70,000 têtes de bétail bovin, sans compter les autres bes tiaux !
            Néanmoins le Conseil d\'Etat s\'occupera  activement du cas exposé par l\'interpellant, et continuera à vouer à l\'arboriculture ses soins les plus assidus. 
             M. Jacques de Riedmatten croit qu \'une enquête par voie du Bulletin officiel n \'offrirait pas suffisamment de garanties, en ce qui concerne l\'étendue et I \'évaluation des dommages.
            M. Bioley., conseiller d \'Etat, répond qu\'il ne serait guère utile de nommer une commission d\'enquête spéciale, dont le coût dépas rait peut-être le chiffre des pertes subies. 
            L\'enquête faite par le Bulletin officiel  pourra, être soumise à un contrôle, s \'il appert que celui-ci soit nécessaire. 
            M. Pignat approuve l\'interpellation de M.  de Riedmatten. Mais il fait renia,rquer que si l\'arboriculture souffre de l\'abondance des lièvres, la pisciculture, elle, souffre de l\'a bonda.nce des canards. Des milliers de ces vo latiles s\'abattent sur nos cours d\'eau, dans le  Bas-Valais principalement, vers l\'embouchure du Rhône. Les dégâts qu\'ils ont occasion nés cette année sont énormes. 
            Les huit à dix mille alevins qui ont été mis dans le canal Stockalper ont entièrement disparu. 
             En conséquence les pêcheurs demandent  que des mesures soient également prises pour  protéger le frai des poissons et 
            N\'y aurait-il pas possibilité d\'accorder des permis spéciaux pour la chasse au canard ?
             M. Couchepin, Conseiller d \'Etat, expose  que cette proposition a déjà été énoncée autrefois, et que le Conseil d\'Etat s\'était alors  adressé aux autorites fédérales, qui répondirent qu\'il y avait danger que les chasseurs autorisés à tirer le canard ne tirent aussi sur le lièvre et que dans ces conditions ces  patentes ne pouvaient pas être accordées. 
            Mais puisqu \'aujourd\'hui on demande l\'ex termination des lièvres et des canards sauva ges, peut-être ce changement total de situation rendra-t-il possible ce qui ne l\'était pas ces années dernières. Le Conseil d\'Etat fera les démarches nécessaires. ', '1907-02-28', 21);
            INSERT INTO debat VALUES (28, '« Tout changement à la base du Système des finances actuel et toute Salvation du taux de l\'impôt seront soumis ä la Sanction du peuple. »', '1906-02-19', 72);
            INSERT INTO debat VALUES (29, '« La présente Constitution ne préjudicie en rien ä ce qui sera arrêté\" par un Concordat réglant les rapports entre l\'Eglise et l\'Etat. » Cette disposition n\'est pas le rétablissement en faveur du clergé des droits anciens dont il avait joui, ni la restitution des biens dont il avait été dépouillé, mais c\'est le premier pas vers la réconciliation. C\'est l\'initiative prise par le pouvoir civil et le témoignage de son désir d\'arriver ä une entente. Nous savons que cette initiative a porté ses fruits et que le peuple valaisan peut être heureux de la Solution donnée ä ce conflit.', '1906-02-19', 73);
            INSERT INTO debat VALUES (30, '« Le Valais est une république démocratique, souveraine dans les limites de la Constitution fédérale, et incorporée comme canton ä la Confédération suisse. La souveraineté réside dans le peuple. Elle est exercée directement par les électeurs et indirectement par les autorités constituées.» En ajoutant le mot « démocratique » au Premier alinéa, la Commission n\'a pas eu l\'idée d\'innover. Comme vous, elle sait que la République du Valais a été démocratique sous l\'empire de la Constitution actuelle ; mais c\'est précisément parce que ce qualificatif ou plutôt cette qualité nécessaire ä une vraie république répond ä la réalité, qu\'il a paru opportun ä la Commission de Adopter. II est du reste incontestable que la Constitution projetée renferme en soi les Principes d\'une évolution vers la démocratie pure, et c\'est aussi pour reconclure ä cette idée que la Commission propose cette adjonction. II a paru nécessaire ä la Commission de donner une autre tournure ä l’alinéa 3 de l\'article 1; car s\'il était exact de dire sous l\'empire de la Constitution actuelle que la forme du gouvernement est celle  de la démocratie représentative, sous réserve des  droits attribues au peuple, il est -plus juste et plus exact de dire avec la nouvelle Constitution que le peuple fait usage lui-même des attributs de la souveraineté, sous réserve des droits qu\'il confère aux autorités constituées.', '1906-02-19', 1);
            INSERT INTO debat VALUES (31, 'La Commission vous propose d\'insérer le principe de la responsabilité de l\'Etat, en cas d\'erreur judiciaire ou d\'arrestation illégale. Ce principe qui découle de l\'équipe aussi bien que du droit absolu, est ä peu près universellement reconnu dans les législations modernes. II sera en pratique d\'une application fort rare, nous en avons la conviction, surtout dans un pays qui vit, en matière pénale, sous le régime de la preuve stricte et formelle et non sous celui de la libre approbation. On se méprendrait cruellement si Ton pouvait croire que cette disposition trouvera son application toutes les fois qu\'un accuse sera acquitte. Non, ce n\'est au contraire que lorsque la révision d\'un arrêt qui a abouti ä une condamnation, sera ordonnée dans les formes prévues par la loi et que la révision amènera la découverte manifeste de l\'erreur, que l\'Etat sera oblige d\'intervenir pour indemniser le malheureux, qui aura été victime de l\'erreur et de réparer dans une mesure équitable le tort cause. II en est de ramée en ce qui concerne l\'arrestation illégale ; le principe de la réparation trouvera son application non pas lorsque l\'arrestation frappera un innocent, mais seulement lorsqu\'elle n\'aura pas été opérée dans les formes et avec les garanties que lui donne la loi. II est du reste évident que la législation devra régie les détails d\'application.', '1906-02-19', 4);
            INSERT INTO debat VALUES (32, 'La Commission a tenu ä donner plus d\'extension au principe, d\'ailleurs incontesté, nous le supposons, de la liberté de faire connaitre ä autrui sa pensée ou son opinion. La liberté de la presse semble d\'une manière générale ne viser que le journalisme. La liberté d\'émettre son opinion dans une réunion, en public ou de la faire connaitre par voie de brochure, etc., doit être également garantie. II est certain d\'autre part que la Commission ne désire pas, par cette modification, voir changer nos mœurs et coutumes en maure de presse et manifestation d\'opinion, et pour cela il est nécessaire que la loi interprêtée par une sage jurisprudence en réprime les abus. Aux termes du texte propose, les abus pourront être reprîmes ou par le droit commun ou par une loi spéciale sur la presse s\'il plait au peuple de s\'en donner une.', '1906-02-19', 8);
            INSERT INTO debat VALUES (33, 'La Commission a voulu donner une Sanction pratique au principe de l’Egalite de traitement des deux langues. C\'est là une satisfaction légitime que personne d\'entre nous ne songera ä refuser ä nos compatriotes de langue allemande.', '1906-02-19', 12);
            INSERT INTO debat VALUES (34, 'La Commission propose que, ä la suite du renouvellement intégral du Grand Conseil, il y ait, avant la Session de Mai, une Session constitutive. L\'avantage de cette innovation est double ; elle permettra au pouvoir législatif nouvellement élu d\'entrer immédiatement en fonction et évitera un dualisme qui pourrait exister au point de vue des compétences entre le Grand Conseil sortant de Charge et celui nouvellement élu, et il aura surtout pour esse de permettre au pouvoir législatif de nommer sa Commission de gestion dans le courant de la session constitutive et d\'examiner la gestion du Conseil d\'Etat pendant la session ordinaire de Mai.', '1906-02-19', 36);
            INSERT INTO debat VALUES (35, 'La Commission vous propose de consacrer par un texte forme ! l\'existence de l\'immunité parlementaire créée par la jurisprudence. II est à désirer que cette disposition ne change pas nos mœurs parlementaires. Elle aura nécessairement pour conséquence, si elle est adoptée, la révision totale du règlement du Grand Conseil au point de vue des mesures de police Intérieure. La majorité de la Commission partage l\'opinion du Conseil d\'Etat au point de vue des incompatibilités, et propose de s\'en tenir ä Celles qui sont prévues par la Constitution actuelle. Par contre une minorité de la Commission propose d\'étendre d\'une manière considérable les cas d\'incompatibilité. Nous nous réservons de revenir en détail sur cette importante question, qu\'il nous suffise de dire que le principe dominant qui a dirigé la décision de la Commission, c\'est que dans un pays où les ressources intellectuelles sont forcement Iimit6es, on ne peut pas priver une assemblée législative des lumières de bon nombre de magistrats qui ont donne la mesure de leur dévouement ä la chose publique et qui ont su concilier le devoir découlant de l\'exercice de leur mandat qu\'ils tiennent du peuple avec celui qui découle de leurs fonctions.', '1906-02-19', 46);
            INSERT INTO debat VALUES (36, 'II a paru nécessaire à la Commission pour consacrer constitutionnellement une pratique constate de proclamer que le tribunal cantonal, c\'est-ä-dire le pouvoir suprême judiciaire doit rendre compte au pouvoir législatif de la marche de l\'administration de la justice.', '1906-02-19', 59);
            INSERT INTO debat VALUES (37, 'La Commission propose, comme Je Conseil d\'Etat, de laisser à la législation le soin de réduire le nombre des juges lorsque le besoin s\'en fera ressentir, ou que les circonstances le permettront. Cette réduction du nombre des juges est en connexion avec la création d\'un tribunal de commerce, ou de tribunaux de prudhommes, dont la Commission prévoit la création, par la voie de la législation. La Constitution étant une œuvre de durée et d\'avenir, il est à espérer que le développement que prendra notre Canton dans le domaine économique et commercial, rendra utile et fructueux la création de ces tribunaux dont l\'heureux esse a été bien souvent proclame. La majorité clé la Commission se range à l\'opinion du Conseil d\'Etat en ce qui concerne la création des Conseils généraux. Une minorité de la Commission par contre n\'a pu s\'empêcher de faire observer combien cette disposition est peu en harmonie avec l\'extension des droits populaires et l\'exercice direct par le peuple des droits de la souveraineté, et vous propose la suppression de cette Innovation.', '1906-02-19', 61);
            INSERT INTO debat VALUES (38, 'La religion catholique, apostolique et romaine est la religion de l’Etat. La liberté de croyance et de conscience, le libre exercice des cultes sont garantis dans les limites compatibles avec l\'ordre public et les bonnes mœurs. La Commission ne propose aucune modification. M. Döfayes. Je ne viens pas vous proposer la suppression de cet article, mais je voudrais vous suggérer une autre rédaction qui corresponde mieux aux principes poses en pareille matière par la Constitution fédérale. Je crois pouvoir affirmer que le canton du Valais est aujourd\'hui le seul parmi les cantons catholiques qui ait conserve dans sa Constitution la proclamation que la religion catholique est la religion de l\'Etat. Tous les autres cantons ont adopté une autre formule et pour mon compte je préférerai la formule suivante qui est celle du canton de Fribourg : « La religion catholique, apostolique et romaine est la religion de la grande majorité du peuple valaisan. » M. le rapporteur français de la Commission nous a dit, il est vrai, que cet article 2 n\'est qu\'une simple formule qui ne tire pas ä conséquence ; mais je répondrai que la Constitution n\'est pas faite de formules mais de Principes. Je voterai quant à moi la rédaction adoptée par la Constitution du canton de Fribourg pour deux raisons : la première est que nous ne nous exposerons pas ä voir critiquer cette disposition par les Chambres fédérales, dont la Sanction ne pourrait ainsi ne pas être obtenue. En esset, déjà en 1876, les Chambres fédérales ont fait des réserves au sujet de cet article 2 pour en limiter la portée. (Ici l\'orateur donne connaissance du rapport de la Commission du Conseil national ä ce sujet.) Ensuite de ce rapport les Chambres fédérales ont accordé leur Sanction sous la réserve que cet article ne pouvait être appliqué que dans le sens des articles 49, 50 et 53 de la Constitution fédérale. II serait donc préférable, ä mon point de vue, d\'adopter des maintenant la rédaction qui a été adoptée par le canton de Fribourg. J\'examinerai encore la question au point de vue de la réalité des faits. Est-ce que le canton du Valais a une religion d\'Etat ? Je ne veux point répondre ä cette question et je préfère charger de ce soin le gouvernement du Valais lui-même. A la suite d\'un recours du gouvernement vaudois au Conseil fédéral contre la lettre pastorale de Mgr Abbet sur la propagande protestante en Valais, le Conseil d\'Etat — cela se passait en 1900 — fut invite ä fournir ses observations et nous lisons dans son memoire le passage suivant : « Le gouvernement vaudois fait erreur en « qualifiant Mgr Abbet de chef de l\'Eglise nationale du Valais. On ne saurait en aucune « Facon attribuer ä l\'évêque Abbet la qualité « de fonctionnaire de l\'Etat. Contrairement ä. « l’organisation d\'autres cantons, l\'Eglise ça- « tholique, dans le canton du Valais, n\'a un « caractère national qu\'en tant qu\'on la considère comme la religion de la presque « unanimité de la population valaisanne. Sous « ce rapport, elle mérite d\'autant plus le titre « nationale que le Valaisan a toujours témoigne son invincible attachement ä l\'Eglise « catholique. < Mais, d tout autre point de vue, cette qualification est inexacte, car en Valais l\'autorité civile et l\'autorité ecclésiastique sont absolument indépendantes l\'une de l\'autre. C\'est en vain que l\'on, rechercherait dans «la législation valaisanne, la nomination de l’Evêque exceptée, les traces d\'un droit « quelconque d\'immixtion des autorités civiles dans les affaires ecclésiastiques. « Au budget de l\'Etat, on ne trouve aucune « allocation ni pour l\'Evêque, ni pour le clergé des paroisses, ni pour le culte. Le < droit public valaisan ne connait ni le placet « ni aucun droit de contrôle sur les man déments ou les publications du clergé et il < n\'est sans doute aucun canton suisse ou « le pouvoir civil soit aussi étranger qu\'en < Valais ä toutes les affaires ecclésiastiques. > Comme vous le voyez par la Citation qui précède, le Conseil d\'Etat lui-même a nettement défini la position respective de l\'Etat et de l’Eglise en Valais. Sans doute le Valaisan a toujours montre son attachement ä la religion catholique, mais l\'Etat, comme tel, n\'a rien ä voir avec le pouvoir ecclésiastique et l\'attitude du gouvernement dans le mémoire cite ci-dessus le démontre suffisamment. Pourquoi des lors affirmer que la religion catholique est la région de l’Etat ? La Séparation chez nous existe matériellement et en droit elle découle de toute notre législation. En conséquence il est absolument illogique de proclamer que la religion catholique est la religion de l\'Etat. Pour n\'être qu\'une formule purement décorative, cette affirmation n\'est pas moins en absolue contradiction avec les faits. La Constitution fédérale met toutes les religions sur un pied de parfaite Egalite; elle leur garantit ä toutes le libre exercice du culte. Par contre n\'est-ce pas en quelque sorte heurter ce principe d’Egalite que de proclamer la religion catholique, religion d’Etat ? N\'est-ce pas s\'exposer ä consacrer au prosit de celle-ci certains avantages et certains Privilèges ? Déjà vous l\'avez fait en Novembre dernier lorsque vous avez introduit dans la loi sur ^Instruction publique la disposition par laquelle le prêtre de la paroisse est membre de droit de la Commission scolaire et cette autre disposition qui assure au clergé deux représentants dans le Conseil de l\'Instruction publique. En maintenant I ‘article 2 dans sa forme actuelle on s\'expose ä voir s\'accentuer ces tendances contre lesquelles on pourrait réagir, ce qui serait de nature ä nous créer des difficultés dont devraient être nanties les autorités fédérales. A tous ces points de vue il serait désirable de voir modifier le texte de l’Article 2 dans le sens indique plus haut. En le faisant nous resterions aussi bons catholiques que les cantons de la Suisse primitive lesquels n\'ont pas craint d\'adopter une rédaction qui cadre avec la Constitution fédérale, sans pour autant avoir porte une atteinte quelconque ä leurs traditions catholiques. Encore une fois, je ne fais pas de proposition formelle; je me borne ä vous communiquer ma manière de voir, vous laissant le soin de solutionner la question dans le sens que vous l\'entendrez.', '1906-02-20', 2);
            INSERT INTO debat VALUES (39, 'Tous les citoyens sont égaux devant la loi. II n\'y a, en Valais, aucun privilège de lieu, de naissance, de personnes et de famille. M. Evéquoz. Une proposition restée en minorité a été faite dans le sein de la Commission ; elle tend à la suppression du second alinéa de cet article, par le motif que cette seconde phrase ne dit rien de plus que la première et qu\'après avoir proclame que tous les citoyens sont égaux, il est superflu de dire qu\'il n\'y a plus de privilège de lieu, de naissance, etc. Je crois pour ce qui me concerne, qu\'il est préférable de maintenir ce texte conforme ä celui de la Constitution fédérale et d\'un bon nombre de cantons et qu\'il n\'est pas inutile d\'affirmer ä nouveau la suppression des Privilèges de lieu, de naissance, etc., et je laisse à l’auteur de cette proposition le soin de la motiver. M. Difayes tient à faire observer à l\'Assemblée une fois pour toutes que ces pro positions dites de minorité n\'émanent pas nécessairement de la minorité du Grand Conseil, mais sont des propositions individuelles faites sans distinction de partis. M. R. Evéquoz. Il est déjà tenu compte de l\'observation de l\'honorable députe M. Defayes dans le texte même du projet; il est vrai qu\'une erreur d\'impression a fait figurer ces propositions sous la rubrique « Propositions de la Minorité » mais seulement dans les 4 premières pages, car à partir de la 5me page cette rubrique est conçue comme suit: «Propositions de Minorité». M. Jos. Stockalper, auteur de la proposition de minorité, la motive comme suit: Le principe de l’Egalite est proclame à l\'alinéa l de lart. 3 et selon mon avis l\'alinéa 2 en est simplement une explication, soit le glossaire. — II est évident et tout le monde le sait qu\'il n\'y plus de privilège de rang et de famille. Dans une Constitution les Principes doivent être poses en termes très précis et on ne doit pas entrer dans le glossaire. Je crois donc qu\'il est absolument suffisant de dire que tous les citoyens sont égaux devant la loi. II y a encore d\'autres considérations qui parlent pour la suppression de cet alinéa ; je relèverai que la Constitution elle-même crée un privilège de lieu par exemple lorsqu\'elle dit que Sion est le chef-lieu du Canton et le siege du Grand Conseil, du Conseil d\'Etat, et de la Cour d\'Appel et de Cassation. Cette disposition constitue un privilège et elle est donc en contradiction avec ce second alinéa. J\'ai consulté différentes Constitution s, des Constitutions très démocratiques et des plus modernes qui ne parlent seulement que du principe de l’Egalite, se contentent de le consacrer et ne renferment pas la disposition du second alinéa. Ici l\'orateur cite la Constitution de 7 ä 8 cantons ä l\'appui de son affirmation. Je vous propose d\'accepter le texte de la Constitution zurichoise ainsi conçu : « Tous les citoyens sont égaux devant la loi pour autant que la Constitution n\'y déroge pas. » Cette rédaction sauve tout ; j\'admets que l\'art. 89 proclamant qu\'il y a incompatibilité entre les fonctions civiles et les fonctions ecclésiastiques sera accepté conformément à la proposition du Conseil d\'Etat. Si vous conservez cet alinéa 2, il y aura alors une contradiction, car d\'un côté à l\'art. 89 vous proclamerez et consacrerez un privilège et par cette disposition de l\'art. 3 vous dites qu\'il n\'y a en Valais aucun privilège de personnes.', '1906-02-20', 3);
            INSERT INTO debat VALUES (40, 'La liberté individuelle et l\'inviolabilité du demieile sont garanties. Nul ne peut être poursuivi ou arrêté et aucune visite domiciliaire ne peut être faite si ce n\'est dans les cas prévus par la loi et avec les formes qu\'elle prescrit. MM. .si. Evequoz et H. Roten, rapporteurs de la Commission. La Commission ne vous propose pas de changement au texte du Conseil d\'Etat, mais eile vous propose l\'adjonction d\'un troisième alinéa ainsi conçu : « L’Etat est tenu d\'indemniser équitablement toute personne victime d\'une erreur judiciaire ou d\'une arrestation illégale. » Le principe de la responsabilité de l’Etat en mesure d\'erreur judiciaire ou d\'arrestation illégale n\'est plus guère conteste. II est admis dans la plupart des legislations modernes. C\'est non seulement un principe d\'équipe, c\'est un principe de justice absolue. Nous savons que cette disposition ne trouvera pas une application fréquente. Elle n\'est par conséquent pas de nature ä nous effrayer au point de vue financier. Dans les pays qui possèdent le Système de la preuve stricte absolue, les erreurs judiciaires sont moins ä redouter que dans les pays qui sont dotés de l\'institution du jury. Si la réparation de 1\'erreur judiciaire est un principe juste qui correspond au droit et au devoir de l\'Etat de punir, eile ne peut être appliquée que dans les cas et suivant les formes prévues par la loi. Ainsi pour constater l\'erreur judiciaire, il faudra nécessairement la révision du premier procès et la proclamation par une nouvelle sentence de l\'erreur commise par la première. En ce qui concerne l\'arrestation illégale, la Commission ne veut viser que l\'arrestation qui aura été faite sans l\'observation des formalités prévues par la loi, et non pas l\'arrestation qui pourrait blâmer une personne dont l\'innocence serait ensuite proclamée. Admettre cette seconde Interprétation, ce serait paralyser l\'exercice de la justice et rendre au juge d\'instruction par trop difficile l\'accomplissement de sa mission. La Commission n\'entend du reste qu\'affirmer un principe, dont elle vous prie de renvoyer l\'exécution ä la loi. M. Bioley, président du Conseil d\'Etat, au nom de ce corps déclare accepter la proposition de la Commission. II fait observer, une fois pour toutes, que le Conseil d\'Etat et la Commission se sont mis d\'accord sur la plupart des modifications rédactionnelles proposées par celle-ci. M. Kluser propose de mettre au premier alinéa le mot « Hausrecht» au lieu des mots « Wohnung » ou « Wohnsitz ». M. D*\" Loräan propose le mot « Wohnsitz » au lieu de Wohnung » M. H. Gentinetta dit qu\'il ne s\'agit pas ici de < Wohnsitz » mais bien de < Wohnung >. Après une petite discussion le renvoi du texte allemand ä la Commission pour nouvel examen est vote. M. H. de Torrente. En principe, je ne veux pas proposer de changement, mais il me parait dangereux d\'adopter d\'emblée ce texte qui laisse place ä une Interprétation beaucoup trop large, si nous ne voulons pas nous exposer ä payer des indemnités toutes les fois qu\'un gendarme aura mis quelqu\'un en état d\'arrestation par suite d\'une erreur. II me semble nécessaire d\'interpréter ce texte d\'une manière formelle et je crois qu\'il y aurait lieu de renvoyer ä une loi spéciale l\'application de ce principe. L\'orateur cite un cas très frappant d\'arrestation qui a été faite par ordre du Département de Justice et Police et oü l\'erreur commise par la gendarmerie au détriment d\'un honorable clergymen ne pouvait néanmoins pas lui être imputée ä saute. La cause de l\'erreur provenait simplement de la mauvaise Compagnie ä laquelle le personnage arrêté s\'était Joint par un malheureux hasard. La victime réclama 10,000 frs de dommages et intérêts, mais il est inutile d\'ajouter qu\'il n\'obtint rien. Voilà un cas qui prouve que Ton est prompt ä s\'en prendre ä la police alors ramée qu\'elle est innocente. II y a lieu de renvoyer cette disposition pour les détails ä une loi qui détermine quand ces indemnités devront être payées. M. R. Eviquoz appuie la proposition de M. de Torrente. La discussion est dose et les deux Premiers alineas sont votes avec l\'adjonction proposée par la Commission et l\'amendement de M. de Torrente qui formera un troisième alinéa de la teneur suivante : « L’Etat est tenu d\'indemniser équitablement toute personne victime d\'une erreur judiciaire et d\'une arrestation illégale. La loi règle l\'application de ce principe.»', '1906-02-20', 4);
            INSERT INTO debat VALUES (41, 'La propriété est inviolable. II ne peut être déroge à ce principe que pour cause d\'utilité publique ou dans les cas prévus par la loi, moyennant une juste et préalable indemnité. La loi peut cependant déterminer des cas d\'expropriation sans indemnité des terrains bourgeoisiaux et communaux pour cause d\'utilité publique, La Commission propose une autre rédaction de la teneur suivante : < La propriété est inviolable. « II ne peut être déroge ä ce principe que pour cause d\'utilité publique, moyennant une juste indemnité et dans les formes prévues prévues par la loi. « La loi peut cependant pour cause d\'utilité publique, déterminer des cas d\'expropriation sans indemnité des terrains bourgeoisiaux et communaux. » M. R. Evéquoz, au nom de la Commission et d\'accord avec le Conseil d\'Etat, fait ressortir qu\'il est préférable de supprimer la condition du paiement préalable de l\'indemnité, car il existe de nombreux cas d\'expropriation sans indemnité préalable. M. L6on Martin fait en substance ressortir les lacunes de notre loi sur les expropriations et voudrait introduire dans la Constitution une disposition analogue ä celle qui existe dans la Constitution genevoise et prévoyant que l\'indemnité judiciaire du ä l\'exproprie est fixée en dernier ressort par l\'autorité judiciaire ; il estime que cette manière de faire présenterait plus de garanties et ne  retarderait pas la Solution de la question, puisque celui qui a requis l\'expropriation n\'a qu\'ä se mettre en possession du terrain ou du droit exproprie sans attendre la Solution de la procédure judiciaire. En outre il saut observer que l\'article constitutionnel en discussion a une teneur trop restreinte et ne tient pas suffisamment compte des cas d\'expropriation qui ont lieu sans indemnité. M. de Preux, chef du Département des Travaux publics, réplique ä M. Martin et démontre a la lumière de la loi que la procédure semi-administrative et semi-judiciaire qu\'il voudrait introduire en matière d\'expropriation et spécialement pour déterminer le montant de l\'indemnité du ä l\'exproprie, n\'est pas admissible. En vertu de notre législation, celui qui a été admis au bénéfice de l\'expropriation est libre, avant de prendre possession du terrain on du droit expropries d\'attendre que l\'indemnité due soit définitivement fluxée et si celle-ci lui parait exagérée, il a parfaitement le droit de se désister, ä condition qu\'il le fasse avant d\'avoir pris possession ou d\'avoir fait usage du terrain ou du droit pour lesquels il a requis l\'expropriation. Introduire la procédure judiciaire, ce serait le plus souvent retarder indéfiniment la fixation de l\'indemnité et partant la prise de possession. D\'autre part, il saut reconnaitre quo l\'observation de M. Martin est fondée lorsqu\'il vient soutenir que l\'article constitutionnel en discussion ne vise pas tous les cas d\'expropriation qui ont lieu sans indemnité. La loi sur les routes nous fournit des exemples. C\'est ainsi que le propriétaire bordier est tenu de bâtir en retraite et de laisser un espace détermine entre sa construction et la rebute. II n\'a droit ä aucune indemnité pour l\'espace de terrain inoccupé. L\'orateur conseille ä M. Martin de retirer sa proposition et de demander par voie de motion la révision de notre législation sur les expropriations qui renferme des lacunes que le Conseil d\'Etat est le premier ä reconnaitre. II parviendra ainsi plus sûrement au but qu\'il veut atteindre, car sa proposition ne peut pas être examinée ä l’occasion des débats sur la Constitution. Celle-ci pose des jalons, des principes généraux en laissant ä des lois spéciales le soin de fixer les détail? de la procédure. M. Martin, au vu des explications fournies par le préopinant, déclare retirer sa proposition. La discussion sur l\'art, 6 est dose et l\'ar7  tacle amende par la Commission et accepte par le Conseil d\'Etat, est vote.', '1906-02-20', 6);
            INSERT INTO debat VALUES (42, 'La presse est libre. La loi pénale en réprime les abus. La Commission propose de remplacer Ie texte du projet du Conseil d\'Etat par le suivant : « La liberté de manifester son opinion verbalement ou par écrit, ainsi que la liberté de la presse sont garanties. La loi en réprime les abus. » La modification apportée a pour but d\'étendre la liberté de manifester son opinion qui peut être exprimée verbale ment ou dans tout écrit et non seulement par la voie de la presse. En supprimant le mot pénal la Commission a voulu, d\'autre part. laisser la porte ouverte ä l\'établissement d\'une loi spéciale sur la presse. Bien que la nécessite d\'une telle loi ne se soit pas fait sentir en Valais jusqu\'ä maintenant en raison des très rares abus de presse qui s\'y commettent, il ne serait pas impossible que par la suite cette loi puisse être utile, sinon nécessaire. M. Defayes propose une modification dans ce sens qu\'au lieu de dire « la loi en réprime les abus » on prévoie dores et déjà une loi spéciale sur la presse en inscrivant dans la Constitution la disposition suivante : « Une loi spéciale en réprime les abus.»', '1906-02-20', 8);
            INSERT INTO debat VALUES (43, ' Le droit de pelition est garanti.
            La loi en regle l\'exercice.
            Cet article est adopte sans Observation.
            La discussion est suspendue et la seance
            levee ä midi et demi et renvoyee an lendemain avec I\'ordre du jour suivant:
            Revision de la Constitution. ', '1906-02-20', 9);
            INSERT INTO debat VALUES (44, 'Le droit de libre etablissement. d\'association et de reunion, la liberte du commerce, de l\'industrie et des arts sont garantis. L\'exercice de ces droits est regle par la loi dans les limites de la Constitution föderale. La Commission propose un autre texte ainsi concu: «Le droit de libre etablissement, d\'association et de reunion, le libre exercice des professions liberales, la liberte du commerce et de l\'industrie sont garantis. « L\'exercice de ces droits est regle par la loi dans les limites de la Constitution fédérale. » M. R. Evequoz, rapporteur, expose que la Commission propose de faire rentrer dans cet article les professions liberales. M. H. de Torrente demande ä la Commission des explications un peu plus etendues au sujet de l\'adjonction qu\'elle propose ä l\'article 10. On nous a dit que la Commission s\'etait bornee ä remanier le texte du projet, mais je ne crois pas qu\'il en soit ainsi, car la modification proposee par la Commission est un changement de fond. Je doute que ce changement soit justifie. Pour ce qui concerne le droit de libre etablissement et d\'association, cela existe en fait et en droit, mais la loi apporte ä la liberte de commerce et d\'industrie de telles reserves, de , telles conditions que souvent ces libertes d\'industrie et de commerce ne sont que de simples leurres et que Ton vous reprend en detail ce que Ton vous a donne en bloc. L\'orateur cite ici la loi sur les substances alimentaires. Vous me direz, ajoute-t-il, qu\'il ne s\'agit que d\'une question de simple police, mais cette loi de simple police se caracterise par la circonstance qu\'elle ne vise que les matieres qui fönt l\'objet du commerce soit au point de vue de leur qualite, soit au point de vue de leur falsiflcation, saus s\'attacher aux personnes et jamais la loi n\'exigera de facultes speciales et personnelles pour l\'exercice d\'une Industrie. Voilä pourquoi le Conseil d\'Etat n\'a pas cru pouvoir proclamer dans cet article le libre exercice des professions liberales. En general, les professions liberales sont reglementees d\'une facon speciale non pas quant ä la maniere d\'exercer la profession, mais les reglements frappent l\'individu qui exerce cette profession et exigent des qualites et des connaissances speciales. II y a donc une difförence essentielle ä faire entre la liberte de commerce et la liberte d\'exercer une profession liberale. Dites-moi quelles sont les professions liberales que l\'on peut exercer sans avoir justifie de ses capacites ? L\'exercice du barreau n\'est pas libre dans notre Canton, donc la liberte d\'exercer l\'avocatie n\'existe pas. II en en est de meme de la pratique de l\'art de guerir, soit de la medecine; nous sommes obliges pour soigner nos malades de prendre un docteur, un medecin porteur d\'un diplöme; pour la profession de notaire cette liberte n\'existe pas non plus; il en est de meme pour la Chirurgie, pour l\'art den. taire, en un mot la plupart des professions liberales sont subordonnees ä des qualites personnelles qui sont, en general, consacrees par un diplöme delivre ä la suite d\'examens passes devant des Commissions d\'Etat cantonales ou föderales. J\'ai ete etonne d\'entendre dire que la Constitution föderale prevoit cette liberte. Cela n\'est pas; la Constitution föderale dit simple- — 108 — •ment: « La liberte de commerce et d\'industrie est garantie dans toute l\'etendue de la •Confederation» mais jamais la Constitution •föderale n\'a proclame cette liberte pour les professions liberales. Vous voulez proclamer le libre exercice des professions liberales, et cependant par notre loi sur la police sanitaire nous nous sommes interdit meme le pouvoir de donner la faculte d\'exercer la medecine dans notre •Canton ä des citoyens valaisans. Je connais un jeune medecin porteur d\'un diplöme etranger, auquel on a refuse, malgre sa qualite de Valaisan, l\'autorisation de pratiquer la medecine dans le Canton et il a du s\'expatrier dans un pays voisin pour pouvoir y exercer son art. Si donc vous introduisez cette clause dans la Constitution, il faudra supprimer les examens d\'avocat et les examens de medecin. Dans le canton de Claris l\'exercice de la medecine est reputee libre, mais c\'est precisement parce que le medecin n\'y est soumis ä aucun examen prealable, mais n\'allez pas pratiquer la medecine dans notre Canton car vous serez immediatement poursuivi par le Conseil de sante. Dans le canton de ^Zürich la liberte de pratiquer l\'avocatie est •complete; mais c\'est precisement parce que «cnacun y est libre draller representer un pa- — 109 — rent ou un ami ä la seule condition que l\'on< ait conflance en lui. La est en esset consacree la liberte de l\'exercice de la medecine et de l\'avocatie, mais cette liberte n\'existe pas partout oü l\'on exige des preuves de capacite personnelle et etablie par un exarnen. Je prie donc la Commission de bien vouloir me donner des explications sur la portee de sa proposition. M. Dr Lorelan, president de la Commission, repond que toute difüculte est levee par la disposition du second alinea de cet article statuant expressement que l\'exercice de ces droits est regle par la loi dans les limites de la Constitution föderale. M. R. Evequoz, rapporteur de la Commission. Comme j\'ai regu le reproche d\'avoir ete trop bref, je donnerai encore les explications suivantes pour justifier le point de vue de la Commission. En ce qui concerne certaines professions nous avons admis le principe qui existe actuellement et nous savons que nous ne sommes pas libres dans notre Canton i d\'exercer certaines professions, aussi disonsnous dans l\'alinea 2 : «l\'exercice de ce droit est regle par la loi dans les limites de la la Constitution föderale.» Nous avons voulu i — 110 — inserer et mentionner daiis notre Constitution le principe de la liberte d\'exercer les professions liberales sous reserve des restrictions apportees par la loi. La Constitution föderale dit: «La liberte de commerce et d\'industrie est garantie dans tonte l\'etendue de la Confederation», nous pouvons donc decreter dans notre Constitution le libre exercice des professions übe rales. Mais d\'un autre cöte, l\'article 33 de la Constitution föderale nous permet de soumettre l\'exercice des professions liberales ä des conditions speciales. Nous avons dejä des dispositions speciales reglant l\'exercice des professions d\'avocat et de medecin. II n\'a jamais ete dans l\'idee de la Commission de supprimer ces restrictions existantes au libre exercice de ces professions; nous n\'avons absolument pas eu l\'idee de changer la Situation actuelle ä ce sujet. Mais il y a d\'autres professions qui sont egalement des professions liberales. Nous avons par exemple la profession d\'architecte que chacun peut pratiquer en Valais ; l\'exercice du Professorat est aussi une profession liberale et eile n\'est soumise ä aucune restriction ; pourquoi alors n\'inscrirons-nous pas dans notre Constitution que l\'exercice de ces professions est libre. On peut pratiquer les difförentes branches du genie; on peut dresser des plans, faire des levees geometriques, etc., tout autant de carrieres qui rentrent dans le domaine des professions liberales et que Ton peut exercer librement. Nous ne demandons que l\'on proclame la liberte de l\'exercice des professions liberales sous reserve des dispositions legales. Aussi j\'espere que l\'honorable depute M. de Torrente sera d\'accord avec la Commission. M. Kuntschen, conseiller d\'Etat, declare que la modiflcation apportee par la Commission au projet du Conseil d\'Etat ne l\'a pas rassure. L\'article 31 de la Constitution föderale proclame la liberte de commerce et d\'industrie et toutes les professions peuvent se mettre au benefice de cet article de la Constitution. II y a toutefois des professions que les cantons peuvent soumettre ä des conditions speciales et c\'est ce que dit l\'article 33 qui decrete que les cantons peuvent exiger des preuves de capacite de ceux qui veulent exercer des professions liberales. Sans l\'article 33 nous ne pourrions pas soumettre l\'exercice de la profession de medecin, l\'exercice du barreau ä des conditions speciales. Gräce ä cet article nous pouvons soumettre nos medecins, nos avocats ä la legislation actuelle ; mais si vous voulez maintenir l\'article tel qu\'il est propose par la Commission, — 112 — tout citoyen pourra venir dire : je me mets au benefice de cet article et par consequent je n\'ai plus besoin de posseder de diplöme. La Commission nous dit: Ce droit constitutionnel nous allons le soumettre ä certaines dispositions Est-ce une exception au principe que vous venez de poser ? je ne le crois pas. Vous ne pouvez pas venir porter atteinte au principe que vous consacrez;, il n\'est pas possible que vous puissiez venir restreindre l\'exercice libre que vous venez de proclamer. Une fois la Constitution votee nous serons oblige d\'accorder ä tous le droit de pratiquer sa profession sans pouvoir la soumettre ä une condition quelconque. Ce serait donc souverainement imprudent de notre part de voter le texte de la Commission. On nous dit que le mot de profession liberale a un sens tres etendu. En general on considere comme une profession liberale la profession de medecin et d\'avocat mais nous ne comprenons pas sous cette denomination Celle de geometre; il saut donc bien nous entendre sur ce que l\'on entend par profession liberale. Par arts nous entendons l\'art du geometre, de l\'ingenieur, mais par le mot de profession liberale nous ne comprenons que les professions pour lesquelles on demande des qualites speciales. — 113 — M. Eviquoz. M. le conseiller d\'Etat Kuntschen discute corame si le second alinea n\'existait pas. La Constitution föderale dit que Ton peut exiger des conditions de ceux qui veulent exercer des professions liberales. 11 ne saut pas oublier que les deux alineas ne fönt qu\'un article et il ne saut pas les separer. La sculpture, la peinture peuvent etre considerees comme des professions liberales. II n\'y aura de restrictions que pour les professions liberales qui seront visees par la loi. M. H. de Torrente. II ne s\'agira pas dans cette loi de regier l\'exercice de ce droit que vous voulez octroyer, il s\'agira de dire dans la loi precisement le contraire de ce qui est dit dans la Constitution et cela est inadraissible. Du Moment oü vous decretez dans la Constitution une liberte absolue vous ne pourrez pas ensuite la restreindre par une loi. Toutes les professions liberales sans exception sont cornprises dans les arts: musique, sculpture, peinture, architecture; toutes ces professions sont libres, du rnoins un certain nombre, mais n\'y aurait-il qu\'une profession qui ne le soit pas que la liberte ne pourrait pas etre proclamee d\'une rnaniere generale pour les professions liberales. II ne saut pas confondre la liberte commerciale et la liberte des professions liberales ; on peut vendre de Pepicerie sans demander la permission ä l\'Etat, mais pour pratiquer l\'avocatie et d\'autres professions liberales, il saut demander Pautorisation de l\'Etat. Je suis tres convaincu d\'avoir raison, mais je ne ferai pas de proposition formelle, pensant que l\'article sera rectifie ä l\'occasion des seconds debats. M. Bioley, president du Conseil d\'Etat, constate que M. de Torrente ne fait point de proposition et que sa maniere de voir n\'est autre que celle du Gouvernement. La discussion est close et l\'article 10 vote ä une grande majorite conformement ä l\'amendement propose par la Commission, d\'accord avec le Conseil d\'Etat.', '1906-02-21', 10);
            INSERT INTO debat VALUES (45, 'Tout citoyen est tenu au Service militaire. L\'application de ce principe est reglee par la legislation föderale et cantonale. II n\'est fait aucune Observation sur le texte de cet article qui est adopte.', '1906-02-21', 11);
            INSERT INTO debat VALUES (46, 'La langue française et la langue allemande sont déclarées nationales. La Commission tout en maintenant le Premier alinéa, en propose un second de la teneur suivante : « L’Egalite de traitement entre les deux langues doit être appliquée dans la législation et dans l\'administration. » M. E. Evequoz, rapporteur. La Commission vous recommande l\'adoption de ce second alinéa. Si en 1802 on a proclamé que la connaissance des deux langues était nécessaire pour être élu ä la Diète, nous pouvons bien un siècle après consacrer le principe de l’égalité de traitement des deux langues. D r Herr Herm, Seiler kritisiert hier die mangelhafte Uebersetzung der deutschen staatsrätlichen Botschuft, welche viele grammatikalische, sprachliche und orthographische Fehler aufweise. Ein so wichtiges. Aktenstück sollte mit mehr Sorgfalt übersetzt werden Er beantragt, statt „Nationalsprachen\" zu sagen: „Landessprachen\", was angenommen wird. M. Bioley, président du Conseil d\'Etat. On a toujours été jusqu\'ici très satisfait du traducteur officiel de l\'Etat. Mais cornine, d\'autre part, je ne doute pas de la compétence de M. Seiler en pareille matière, nous chercherons d\'oü peut provenir le kalt par lui Signale. S\'il devait etre imputable au traducteur ordinaire, on ne pourrait que s\'en étonner, et nous ne doutons pas d\'ailleurs que cela ne se répètera plus. M. Burgener, vice-président du Conseil d\'Etat. II ine semble que les Observation.-; de M. le docteur Hermann Seiler sont exagérées. Cela pourrait provenir des trop grandes occupations du traducteur, ä un moment donne, que les traductions n\'aient pas tout le fini désire, mais, je crois pouvoir le declarer, le traducteur jusqu\'ä présent a prouve toute sa compétence pour ce travail de traduction; et il n\'est pas admissible que pour quelques fautes, qui sont plutôt des kaute» d\'impression, on vienne la révoquer en doute. La discussion est close et l\'article 12 est vote tel que propose avec ramendement de la Commission.', '1906-02-21', 12);
            INSERT INTO debat VALUES (47, 'L\'instruction publique est placée sous la direction et la haute surveillance de l\'Etat. II en est de même de l\'instruction primaire privée. L\'instruction primaire est obligatoire et, dans les écoles publiques, gratuite. La liberté d\'enseignement est garantie sous réserve des dispositions légales concernant l\'école primaire. La Commission propose une modification purement rédactionnelle et le texte suivant : « L\'instruction publique et l\'instruction primaire privée sont placées sous la direction et la haute surveillance de l\'Etat. « L\'instruction primaire est obligatoire -y elle est gratuite dans les écoles publiques. « La. Liberté d\'enseignement est garantie sous réserve des dispositions légales. » M. R. Evéquoz, rapporteur de la Commission, expose le point de vue de la Commission. Nous avons adopté le principe pose par l\'article 13 qui est base sur l\'article 27 de la Constitution fédérale dont le deuxième alinéa est de la teneur suivante : c Les cantons pourvoient ä l’instruction primaire, qui doit être suffisante et placée exclusivement sous la direction de l’autorité civile. Elle est obligatoire et, dans les écoles publiques, gratuite. » En 1876, lors de la discussion aux Chambres fédérales de la Constitution valaisanne •de 1875, une Observation a été faite au sujet de l\'article 11. Tandis que la Constitution fédérale veut que l’enseignement prive, comme l’enseignement primaire, soit soumis ä la surveillance de l’Etat, l\'article 11 ne prévoyait aucune garantie de cette surveillance dans les écoles primaires privées. C\'est pour tenir compte de cette Observation faite en 1876 que l’Etat a proclame que l’instruction publique et l’instruction primaire privée sont placées sous la haute surveillance de l’Etat. L\'article 13 pose quatre grands principes fondamentaux : 1° la liberté d\'enseignement. Le principe de la liberté d\'enseignement a été une conquête de l’esprit moderne et le Conseil d\'Etat comme la Commission a tenm ä le consacrer. Ce principe que l\'on croyait définitivement acquis est actuellement fortement attaque dans certains pays, c\'est pourquoi nous voulons, nous, proclamer hautement la liberté de l\'enseignement. Le second principe est que l\'instruction publique en général, primaire et privée, est placée sous la surveillance de l\'Etat. C\'est là une Obligation imposée par la Constitution fédérale. Le troisième principe est que l\'instruction primaire est obligatoire. II n\'est pas nécessaire d\'insister sur ces principes qui ont permis au Valais de donner une grande extension ä son Instruction. Le quatrième principe est la gratuite de renseignement. La Commission a placé sur le meine rang l\'instruction publique et ^Instruction primaire privée. M. Bioley, président du Conseil d\'Etat. Le Conseil d\'Etat est d\'accord, quant au fond, avec la Commission ; il n\'y a guere qu\'une question de rédaction qui nous sépare. Bien que celle-ci soit peu importante, nous croyons cependant que le texte propose par le Conseil d\'Etat répond mieux ä la question et je vous en demande le maintien. Nous demandons notamment que la rédaction des alinéas 1 et 3 soit conservée. L\'instruction publique est placée sous la, — 119 - haute surveillance de l\'Etat, voilä le principe général ; il y a une exception ä cette règle, c\'est pour l\'instruction primaire privée. L\'insertion de cette disposition que nous vous proposons nous est imposée par Ia Constitution fédérale. Pour ce qui concerne le trgisieine alinéa, nous ne pouvons pas accepter la proposition de la Commission. Le texte propose par le Conseil d\'Etat dit : < concernant l\'école primaire ,. Cr, la Commission retranche ces derniers mots, ce ä quoi nous ne saurions donner la main. M. Evequoz. J\'ai dit d\'avance que nous acceptions le texte de l’alinéa 3 du Conseil d’Etat ; toutefois, il faudrait tourner la phrase autrement, dans ce sens: « La liberté d\'enseignement est garantie sous réserve, pour ce qui concerne l\'école primaire, des dispositions légales ». La discussion est dose ; les deux Premiers alinéas de l\'article 13 sont votes conformément aux propositions de la Commission ; par contre, le troisième alinéa propose par le Conseil d\'Etat est adopte.', '1906-02-21', 13);
            INSERT INTO debat VALUES (48, 'L\'Etat encourage et protège l\'enseignement professionnel concernant le commerce, l\'industrie, l\'agriculture et les arts et métiers', '1906-02-21', 14);
            INSERT INTO debat VALUES (49, 'L\'Etat protège l\'agriculture, l\'industrie et le commerce et en général toutes les branches de l\'économie publique intéressant le canton.', '1906-02-21', 15);
            INSERT INTO debat VALUES (50, 'L\'Etat subventionne dans la mesure de ses ressources l\'élevage du bétail, l ‘industrie laitrfere, la viticulture, l\'arboriculture, les améliorations du sol et la sylvi- •culture.', '1906-02-21', 16);
        ";

        $conn->executeUpdate($sql);
    }

    public function insertDebatsIntervenants()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO debat_intervenant VALUES (1, 1);
INSERT INTO debat_intervenant VALUES (1, 2);
INSERT INTO debat_intervenant VALUES (1, 3);
INSERT INTO debat_intervenant VALUES (2, 1);
INSERT INTO debat_intervenant VALUES (3, 4);
INSERT INTO debat_intervenant VALUES (3, 5);
INSERT INTO debat_intervenant VALUES (3, 6);
INSERT INTO debat_intervenant VALUES (3, 7);
INSERT INTO debat_intervenant VALUES (3, 8);
INSERT INTO debat_intervenant VALUES (4, 4);
INSERT INTO debat_intervenant VALUES (4, 5);
INSERT INTO debat_intervenant VALUES (4, 8);
INSERT INTO debat_intervenant VALUES (4, 9);
INSERT INTO debat_intervenant VALUES (5, 5);
INSERT INTO debat_intervenant VALUES (5, 6);
INSERT INTO debat_intervenant VALUES (5, 10);
INSERT INTO debat_intervenant VALUES (5, 11);
INSERT INTO debat_intervenant VALUES (5, 9);
INSERT INTO debat_intervenant VALUES (5, 12);
INSERT INTO debat_intervenant VALUES (5, 13);
INSERT INTO debat_intervenant VALUES (6, 14);
INSERT INTO debat_intervenant VALUES (6, 15);
INSERT INTO debat_intervenant VALUES (6, 5);
INSERT INTO debat_intervenant VALUES (6, 6);
INSERT INTO debat_intervenant VALUES (6, 16);
INSERT INTO debat_intervenant VALUES (11, 9);
INSERT INTO debat_intervenant VALUES (12, 9);
INSERT INTO debat_intervenant VALUES (12, 11);
INSERT INTO debat_intervenant VALUES (13, 24);
INSERT INTO debat_intervenant VALUES (13, 16);
INSERT INTO debat_intervenant VALUES (13, 17);
INSERT INTO debat_intervenant VALUES (13, 9);
INSERT INTO debat_intervenant VALUES (15, 13);
INSERT INTO debat_intervenant VALUES (15, 11);
INSERT INTO debat_intervenant VALUES (15, 4);
INSERT INTO debat_intervenant VALUES (15, 9);
INSERT INTO debat_intervenant VALUES (16, 9);
INSERT INTO debat_intervenant VALUES (16, 13);
INSERT INTO debat_intervenant VALUES (17, 18);
INSERT INTO debat_intervenant VALUES (17, 5);
INSERT INTO debat_intervenant VALUES (17, 21);
INSERT INTO debat_intervenant VALUES (18, 19);
INSERT INTO debat_intervenant VALUES (18, 4);
INSERT INTO debat_intervenant VALUES (18, 8);
INSERT INTO debat_intervenant VALUES (20, 19);
INSERT INTO debat_intervenant VALUES (22, 20);
INSERT INTO debat_intervenant VALUES (22, 14);
INSERT INTO debat_intervenant VALUES (22, 1);
INSERT INTO debat_intervenant VALUES (22, 9);
INSERT INTO debat_intervenant VALUES (22, 19);
INSERT INTO debat_intervenant VALUES (22, 5);
INSERT INTO debat_intervenant VALUES (23, 19);
INSERT INTO debat_intervenant VALUES (23, 8);
INSERT INTO debat_intervenant VALUES (23, 5);
INSERT INTO debat_intervenant VALUES (24, 10);
INSERT INTO debat_intervenant VALUES (24, 6);
INSERT INTO debat_intervenant VALUES (24, 21);
INSERT INTO debat_intervenant VALUES (24, 5);
INSERT INTO debat_intervenant VALUES (25, 5);
INSERT INTO debat_intervenant VALUES (25, 9);
INSERT INTO debat_intervenant VALUES (26, 19);
INSERT INTO debat_intervenant VALUES (26, 23);
INSERT INTO debat_intervenant VALUES (27, 8);
INSERT INTO debat_intervenant VALUES (27, 16);
INSERT INTO debat_intervenant VALUES (27, 24);
INSERT INTO debat_intervenant VALUES (27, 9);
INSERT INTO debat_intervenant VALUES (38, 19);
INSERT INTO debat_intervenant VALUES (38, 25);
INSERT INTO debat_intervenant VALUES (39, 19);
INSERT INTO debat_intervenant VALUES (39, 5);
INSERT INTO debat_intervenant VALUES (39, 28);
INSERT INTO debat_intervenant VALUES (40, 5);
INSERT INTO debat_intervenant VALUES (40, 9);
INSERT INTO debat_intervenant VALUES (40, 4);
INSERT INTO debat_intervenant VALUES (41, 5);
INSERT INTO debat_intervenant VALUES (42, 19);
INSERT INTO debat_intervenant VALUES (44, 5);
INSERT INTO debat_intervenant VALUES (44, 4);
INSERT INTO debat_intervenant VALUES (44, 27);
INSERT INTO debat_intervenant VALUES (46, 5);
INSERT INTO debat_intervenant VALUES (46, 9);
INSERT INTO debat_intervenant VALUES (46, 21);
INSERT INTO debat_intervenant VALUES (47, 5);
INSERT INTO debat_intervenant VALUES (47, 9);
        ";

        $conn->executeUpdate($sql);
    }


    // /**
    //  * @return Article[] Returns an array of Article objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
