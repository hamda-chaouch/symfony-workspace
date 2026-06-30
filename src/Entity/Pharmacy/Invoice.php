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

use App\Entity\Pharmacy\Vente;
use App\Repository\Pharmacy\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vente $vente = null;

    #[ORM\Column(length: 50)]
    private ?string $invoice_number = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?float $total_ht = null;

    #[ORM\Column]
    private ?float $total_tva = null;

    #[ORM\Column]
    private ?float $total_ttc = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paid_at = null;

    /**
     * @var Collection<int, CreditNote>
     */
    #[ORM\OneToMany(targetEntity: CreditNote::class, mappedBy: 'invoice')]
    private Collection $creditNotes;

    public function __construct()
    {
        $this->creditNotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoice_number;
    }

    public function setInvoiceNumber(string $invoice_number): static
    {
        $this->invoice_number = $invoice_number;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paid_at;
    }

    public function setPaidAt(?\DateTimeImmutable $paid_at): static
    {
        $this->paid_at = $paid_at;

        return $this;
    }

    /**
     * @return Collection<int, CreditNote>
     */
    public function getCreditNotes(): Collection
    {
        return $this->creditNotes;
    }

    public function addCreditNote(CreditNote $creditNote): static
    {
        if (!$this->creditNotes->contains($creditNote)) {
            $this->creditNotes->add($creditNote);
            $creditNote->setInvoice($this);
        }

        return $this;
    }

    public function removeCreditNote(CreditNote $creditNote): static
    {
        if ($this->creditNotes->removeElement($creditNote)) {
            // set the owning side to null (unless already changed)
            if ($creditNote->getInvoice() === $this) {
                $creditNote->setInvoice(null);
            }
        }

        return $this;
    }
}
