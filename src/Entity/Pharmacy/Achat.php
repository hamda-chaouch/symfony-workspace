<?php

namespace App\Entity\Pharmacy;

use App\Repository\Pharmacy\AchatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchatRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Achat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_achat = null;

    #[ORM\ManyToOne(inversedBy: 'achats')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_ht = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_tva = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_ttc = null;

    #[ORM\Column(length: 20)]
    private ?string $etat = null;

/*
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $tva_pourcentage = null;
*/
    /**
     * @var Collection<int, AchatItem>
     */
    #[ORM\OneToMany(mappedBy: 'achat', targetEntity: AchatItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $achatItems;

    public function __construct()
    {
        $this->achatItems = new ArrayCollection();
        $this->total_ht = 0.0;
        $this->total_tva = 0.0;
        $this->total_ttc = 0.0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDateAchat(): ?\DateTimeImmutable
    {
        return $this->date_achat;
    }

    public function setDateAchat(\DateTimeImmutable $date_achat): static
    {
        $this->date_achat = $date_achat;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getTotalHt(): ?float
    {
        return $this->total_ht;
    }

    public function setTotalHt(float $total_ht): static
    {
        $this->total_ht = $total_ht;

        return $this;
    }

    public function getTotalTva(): ?float
    {
        return $this->total_tva;
    }

    public function setTotalTva(float $total_tva): static
    {
        $this->total_tva = $total_tva;

        return $this;
    }

    public function getTotalTtc(): ?float
    {
        return $this->total_ttc;
    }

    public function setTotalTtc(float $total_ttc): static
    {
        $this->total_ttc = $total_ttc;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }
/*
    public function getTvaPourcentage(): ?float
    {
        return $this->tva_pourcentage;
    }

    public function setTvaPourcentage(?float $tva_pourcentage): static
    {
        $this->tva_pourcentage = $tva_pourcentage;
        return $this;
    }
*/

    /**
     * @return Collection<int, AchatItem>
     */
    public function getAchatItems(): Collection
    {
        return $this->achatItems;
    }

    public function addAchatItem(AchatItem $achatItem): static
    {
        if (!$this->achatItems->contains($achatItem)) {
            $this->achatItems->add($achatItem);
            $achatItem->setAchat($this);
        }

        return $this;
    }

    public function removeAchatItem(AchatItem $achatItem): static
    {
        if ($this->achatItems->removeElement($achatItem)) {
            // set the owning side to null (unless already changed)
            if ($achatItem->getAchat() === $this) {
                $achatItem->setAchat(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotals(): void
    {
        $totalHt = 0.0;
        $tvaRate = 0.0;
        $lineHt = 0.0;
        $totalTva = 0.0;
        foreach ($this->getAchatItems() as $item) {
             $lineHt += $item->getQuantite() * $item->getPrixUnitaireHt();
             $totalHt += $lineHt;
             $product = $item->getProduit();
             $tvaRate = $item->getTvaPourcentage() ?? $item->getProduit()->getTvaPourcentage() ?? 0;
             $totalTva += $lineHt * ($tvaRate / 100);
        }
        $this->total_ht = $totalHt;
        //$tvaRate = $this->tva_pourcentage ?? 0;
        $this->total_tva = $totalTva;
        $this->total_ttc = $totalHt + $this->total_tva;
    }

}
