<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    private $kernelProjectDir;

    // le constructeur indique que nous avons ici besoin d'un paramètre, le kernelProjectDir.
    // Nous allons passer ce paramètre en le déclarant dans notre fichier config/services.yaml.
    // Il s'agit d'un paramètre par défaut introduit dans Symfony 4.
    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }


    public function getFunctions()
    {
        return array(
            // on déclare notre fonction.
            // Le 1er paramètre est le nom de la fonction utilisée dans notre template
            // le 2ème est un tableau dont le 1er élément représente la classe où trouver la fonction associée
            // (en l'occurence $this, c'est à dire cette classe puisque notre fonction est déclarée un peu plus bas).
            // Et le 2ème élément du tableau est le nom de la fonction associée qui sera appelée lorsque nous
            // l'utiliserons dans notre template.
            new TwigFunction('assetExists', array($this, 'assetExists')),
            new TwigFunction('boldIntervenantName', array($this, 'boldIntervenantName')),
            new TwigFunction('findItemById', array($this, 'findItemById')),
        );
    }

    // chemin relatif de notre fichier en paramètre
    public function assetExists($fileRelativePath)
    {
        if ($fileRelativePath === null)
            return false;

        // si le fichier passé en paramètre de la fonction existe, on retourne true,
        // sinon on retourne false.
        return file_exists($this->kernelProjectDir . "/public/" . $fileRelativePath) ? true : false;
    }

    public function boldIntervenantName(string $texte, $intervenants, $colors, $ABSURL)
    {
        $noms = array();
        foreach ($intervenants as $intervenant) {
            array_push($noms, $intervenant->getNom());
        }

        $noms = array_count_values($noms);

        foreach ($intervenants as $key => $intervenant) {
            if ($noms[$intervenant->getNom()] > 1)
                $texte = str_replace($intervenant->getPrenom() . ' ' . $intervenant->getNom(), $intervenant->getPrenom() . ' <a href="' . $ABSURL . '/constitutions-time-machine/boussole/intervenant/details/' . $intervenant->getId() . '" style="background: ' . $colors[$key][0] . '; color: ' . $colors[$key][1] . '">' . $intervenant->getNom() . '</a>', $texte);
            else
                $texte = str_replace($intervenant->getNom(), '<a href="' . $ABSURL . '/constitutions-time-machine/boussole/intervenant/details/' . $intervenant->getId() . '" style="background: ' . $colors[$key][0] . '; color: ' . $colors[$key][1] . '">' . $intervenant->getNom() . '</a>', $texte);
        }

        return $texte;
    }

    public function findItemById($array, $id)
    {
        foreach ($array as $item) {
            if ($id == $item->getId()) {
                return $item;
            }
        }

        return null;
    }
}