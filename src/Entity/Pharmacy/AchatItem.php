<?php

namespace App\Entity\Pharmacy;

use App\Repository\Pharmacy\AchatItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchatItemRepository::class)]
#[ORM\HasLifecycleCallbacks] 
class AchatItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'achatItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Achat $achat = null;

    #[ORM\ManyToOne(inversedBy: 'achatItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $produit = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column]
    private ?float $prix_unitaire_ht = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_ligne_ht = null;

    #[ORM\Column(nullable: true)]
    private ?float $tva_pourcentage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAchat(): ?Achat
    {
        return $this->achat;
    }

    public function setAchat(?Achat $achat): static
    {
        $this->achat = $achat;

        return $this;
    }

    public function getProduit(): ?Stock
    {
        return $this->produit;
    }

    public function setProduit(?Stock $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaireHt(): ?float
    {
        return $this->prix_unitaire_ht;
    }

    public function setPrixUnitaireHt(float $prix_unitaire_ht): static
    {
        $this->prix_unitaire_ht = $prix_unitaire_ht;

        return $this;
    }

    public function getTotalLigneHt(): ?float
    {
        return $this->total_ligne_ht;
    }

    public function setTotalLigneHt(float $total_ligne_ht): static
    {
        $this->total_ligne_ht = $total_ligne_ht;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateLineTotal(): void
    {
        if ($this->quantite !== null && $this->prix_unitaire_ht !== null) {
            $this->total_ligne_ht = $this->quantite * $this->prix_unitaire_ht;
        }
    }

    public function getTvaPourcentage(): ?float
    {
        return $this->tva_pourcentage;
    }

    public function setTvaPourcentage(?float $tva_pourcentage): static
    {
        $this->tva_pourcentage = $tva_pourcentage;

        return $this;
    }

}
