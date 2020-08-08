<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 */
class Article
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $texte;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nomArticle;

    /**
     * @ORM\ManyToOne(targetEntity=Theme::class, inversedBy="articles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $theme;

    /**
     * @ORM\ManyToMany(targetEntity=Loi::class, inversedBy="articles")
     */
    private $lois;

    /**
     * @ORM\OneToMany(targetEntity=Debat::class, mappedBy="article")
     */
    private $debats;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="articles")
     */
    private $commission;

    /**
     * @ORM\OneToMany(targetEntity=Archive::class, mappedBy="article")
     */
    private $archives;

    public function __construct()
    {
        $this->lois = new ArrayCollection();
        $this->debats = new ArrayCollection();
        $this->archives = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getNomArticle(): ?string
    {
        return $this->nomArticle;
    }

    public function setNomArticle(?string $nomArticle): self
    {
        $this->nomArticle = $nomArticle;

        return $this;
    }

    public function getTheme(): ?theme
    {
        return $this->theme;
    }

    public function setTheme(?theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection|loi[]
     */
    public function getLois(): Collection
    {
        return $this->lois;
    }

    public function addLoi(loi $loi): self
    {
        if (!$this->lois->contains($loi)) {
            $this->lois[] = $loi;
        }

        return $this;
    }

    public function removeLoi(loi $loi): self
    {
        if ($this->lois->contains($loi)) {
            $this->lois->removeElement($loi);
        }

        return $this;
    }

    /**
     * @return Collection|debat[]
     */
    public function getDebats(): Collection
    {
        return $this->debats;
    }

    public function addDebat(debat $debat): self
    {
        if (!$this->debats->contains($debat)) {
            $this->debats[] = $debat;
        }

        return $this;
    }

    public function removeDebat(debat $debat): self
    {
        if ($this->debats->contains($debat)) {
            $this->debats->removeElement($debat);
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * @return Collection|Archive[]
     */
    public function getArchives(): Collection
    {
        return $this->archives;
    }

    public function addArchive(Archive $archive): self
    {
        if (!$this->archives->contains($archive)) {
            $this->archives[] = $archive;
            $archive->setArticle($this);
        }

        return $this;
    }

    public function removeArchive(Archive $archive): self
    {
        if ($this->archives->contains($archive)) {
            $this->archives->removeElement($archive);
            // set the owning side to null (unless already changed)
            if ($archive->getArticle() === $this) {
                $archive->setArticle(null);
            }
        }

        return $this;
    }
}
