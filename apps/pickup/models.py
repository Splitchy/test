from __future__ import annotations

import uuid
from django.db import models

from apps.geo.models import City
from apps.users.models import ClientProfile


class PickupStatus(models.TextChoices):
    EN_ATTENTE_RAMASSAGE = 'EN_ATTENTE_RAMASSAGE', 'En attente ramassage'
    EN_PREPARATION = 'EN_PREPARATION', 'En préparation'
    RAMASSE = 'RAMASSE', 'Ramassé'
    MISE_EN_DISTRIBUTION = 'MISE_EN_DISTRIBUTION', 'Mise en distribution'
    LIVRE = 'LIVRE', 'Livré'
    REFUSE = 'REFUSE', 'Refusé'
    ANNULE = 'ANNULE', 'Annulé'


def generate_tracking() -> str:
    return uuid.uuid4().hex[:10].upper()


class PickupOrder(models.Model):
    tracking_code = models.CharField(max_length=20, unique=True, default=generate_tracking)
    client = models.ForeignKey(ClientProfile, on_delete=models.CASCADE, related_name='pickup_orders')
    product = models.CharField(max_length=255)
    quantity = models.PositiveIntegerField(default=1)
    phone = models.CharField(max_length=20)
    city = models.ForeignKey(City, on_delete=models.CASCADE)
    address = models.CharField(max_length=500)
    note = models.TextField(blank=True)
    price = models.DecimalField(max_digits=10, decimal_places=2)
    status = models.CharField(max_length=25, choices=PickupStatus.choices, default=PickupStatus.EN_ATTENTE_RAMASSAGE)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.tracking_code} ({self.status})"