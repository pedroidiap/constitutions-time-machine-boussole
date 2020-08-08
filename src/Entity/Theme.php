<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ThemeRepository::class)
 */
class Theme
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $couleur;

    /**
     * @ORM\OneToMany(targetEntity=Loi::class, mappedBy="theme")
     */
    private $lois;

    /**
     * @ORM\OneToMany(targetEntity=Article::class, mappedBy="theme")
     */
    private $articles;

    public function __construct()
    {
        $this->lois = new ArrayCollection();
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * @return Collection|Loi[]
     */
    public function getLois(): Collection
    {
        return $this->lois;
    }

    public function addLoi(Loi $loi): self
    {
        if (!$this->lois->contains($loi)) {
            $this->lois[] = $loi;
            $loi->setTheme($this);
        }

        return $this;
    }

    public function removeLoi(Loi $loi): self
    {
        if ($this->lois->contains($loi)) {
            $this->lois->removeElement($loi);
            // set the owning side to null (unless already changed)
            if ($loi->getTheme() === $this) {
                $loi->setTheme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Article[]
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
            $article->setTheme($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->contains($article)) {
            $this->articles->removeElement($article);
            // set the owning side to null (unless already changed)
            if ($article->getTheme() === $this) {
                $article->setTheme(null);
            }
        }

        return $this;
    }
}
