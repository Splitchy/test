from __future__ import annotations

from django.db import models
from django.utils import timezone

from apps.users.models import ClientProfile, LivreurProfile
from apps.pickup.models import PickupOrder


class InvoiceStatus(models.TextChoices):
    OUVERT = 'OUVERT', 'Ouvert'
    FERME = 'FERME', 'Fermé'


class ClientInvoice(models.Model):
    client = models.ForeignKey(ClientProfile, on_delete=models.CASCADE, related_name='invoices')
    orders = models.ManyToManyField(PickupOrder)
    status = models.CharField(max_length=10, choices=InvoiceStatus.choices, default=InvoiceStatus.OUVERT)
    created_at = models.DateTimeField(default=timezone.now)

    def total_amount(self):
        return sum(order.price for order in self.orders.all())

    def __str__(self):
        return f"Facture Client #{self.id} - {self.status}"


class LivreurInvoice(models.Model):
    livreur = models.ForeignKey(LivreurProfile, on_delete=models.CASCADE, related_name='invoices')
    orders = models.ManyToManyField(PickupOrder)
    status = models.CharField(max_length=10, choices=InvoiceStatus.choices, default=InvoiceStatus.OUVERT)
    created_at = models.DateTimeField(default=timezone.now)

    def total_amount(self):
        # uses livreur fee; simplified constant value for demo
        return self.orders.count() * self.livreur.delivery_fee

    def __str__(self):
        return f"Facture Livreur #{self.id} - {self.status}"