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


<?php

namespace App\Entity\Pharmacy;

use App\Entity\Pharmacy\Invoice;
use App\Repository\Pharmacy\VenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_vente = null;

/*
    #[ORM\ManyToOne(inversedBy: 'ventes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $client = null;
*/
    #[ORM\Column(type: 'float')]
    private ?float $total_ht = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_tva = null;

    #[ORM\Column(type: 'float')]
    private ?float $total_ttc = null;

    #[ORM\Column(length: 20, options: ['default' => 'draft'])]
    private ?string $etat = 'draft';
/*
    #[ORM\Column(type: 'float',nullable: true)]
    private ?float $tva_pourcentage = null;
*/
    /**
     * @var Collection<int, VenteItem>
     */
    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: VenteItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $venteItems;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'vente')]
    private Collection $invoices;

    public function __construct()
    {
        $this->venteItems = new ArrayCollection();
        $this->invoices = new ArrayCollection();
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

    public function getDateVente(): ?\DateTimeImmutable
    {
        return $this->date_vente;
    }

    public function setDateVente(\DateTimeImmutable $date_vente): static
    {
        $this->date_vente = $date_vente;

        return $this;
    }
/*
    public function getClient(): ?Customer
    {
        return $this->client;
    }

    public function setClient(?Customer $client): static
    {
        $this->client = $client;

        return $this;
    }
*/
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

    public function getEtat(): string
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
            $venteItem->setVente($this);
        }

        return $this;
    }

    public function removeVenteItem(VenteItem $venteItem): static
    {
        if ($this->venteItems->removeElement($venteItem)) {
            // set the owning side to null (unless already changed)
            if ($venteItem->getVente() === $this) {
                $venteItem->setVente(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotals(): void
    {
        $totalHt = 0.0;
        $totalTva = 0.0;
        $lineHt = 0.0;
        foreach ($this->getVenteItems() as $item) {
            $lineHt = $item->getQuantite() * $item->getPrixUnitaireHt();
            //$totalHt += $item->getTotalLigneHt();
            $totalHt += $lineHt;
            $product = $item->getProduit(); 
            $tvaRate = $product->getTvaPourcentage() ?? $item->getProduit()->getTvaPourcentage() ?? 0;
            $totalTva += $lineHt * ($tvaRate / 100);

        }
        $this->total_ht = $totalHt;
        //$tvaRate = $this->tva_pourcentage ?? 0;
        //$this->total_tva = $totalHt * ($tvaRate / 100);
        $this->total_tva = $totalTva;
        $this->total_ttc = $totalHt + $this->total_tva;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setVente($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getVente() === $this) {
                $invoice->setVente(null);
            }
        }

        return $this;
    }

}
