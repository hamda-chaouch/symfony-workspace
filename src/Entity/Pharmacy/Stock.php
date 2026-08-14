<?php
/*
 * Copyright (c) 2025 Hamda Chaouch.
 *
 * Licensed under the Apache License, Version 2.0 (the License);
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an AS IS BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */



namespace App\Entity\Pharmacy;

use App\Repository\Pharmacy\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?float $prix_achat = null;

    #[ORM\Column(nullable: true)]
    private ?float $prix_gros = null;

    #[ORM\Column]
    private ?float $prix_detail = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantite_min = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantite_max = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Category $categorie = null;

    /**
     * @var Collection<int, AchatItem>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: AchatItem::class, orphanRemoval: true)]
    private Collection $achatItems;

    /**
     * @var Collection<int, VenteItem>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: VenteItem::class, orphanRemoval: true)]
    private Collection $venteItems;

    //#[ORM\Column(nullable: true)]
    //private ?float $tva_pourcentage = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_expiration = null;

    public function __construct()
    {
        $this->achatItems = new ArrayCollection();
        $this->venteItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getPrixAchat(): ?float
    {
        return $this->prix_achat;
    }

    public function setPrixAchat(float $prix_achat): static
    {
        $this->prix_achat = $prix_achat;

        return $this;
    }

    public function getPrixGros(): ?float
    {
        return $this->prix_gros;
    }

    public function setPrixGros(?float $prix_gros): static
    {
        $this->prix_gros = $prix_gros;

        return $this;
    }

    public function getPrixDetail(): ?float
    {
        return $this->prix_detail;
    }

    public function setPrixDetail(float $prix_detail): static
    {
        $this->prix_detail = $prix_detail;

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

    public function getQuantiteMin(): ?int
    {
        return $this->quantite_min;
    }

    public function setQuantiteMin(?int $quantite_min): static
    {
        $this->quantite_min = $quantite_min;

        return $this;
    }

    public function getQuantiteMax(): ?int
    {
        return $this->quantite_max;
    }

    public function setQuantiteMax(?int $quantite_max): static
    {
        $this->quantite_max = $quantite_max;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getCategorie(): ?Category
    {
        return $this->categorie;
    }

    public function setCategorie(?Category $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

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
            $achatItem->setProduit($this);
        }

        return $this;
    }

    public function removeAchatItem(AchatItem $achatItem): static
    {
        if ($this->achatItems->removeElement($achatItem)) {
            // set the owning side to null (unless already changed)
            if ($achatItem->getProduit() === $this) {
                $achatItem->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VenteItem>
     */
    public function getVenteItems(): Collection
    {
        return $this->venteItems;
    }

    public function addVenteItem(VenteItem $venteItem): static
    {
        if (!$this->venteItems->contains($venteItem)) {
            $this->venteItems->add($venteItem);
            $venteItem->setProduit($this);
        }

        return $this;
    }

    public function removeVenteItem(VenteItem $venteItem): static
    {
        if ($this->venteItems->removeElement($venteItem)) {
            // set the owning side to null (unless already changed)
            if ($venteItem->getProduit() === $this) {
                $venteItem->setProduit(null);
            }
        }

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

    public function getDateExpiration(): ?\DateTime
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(?\DateTime $date_expiration): static
    {
        $this->date_expiration = $date_expiration;

        return $this;
    }
}
