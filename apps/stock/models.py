from __future__ import annotations

import uuid
from django.db import models

from apps.users.models import ClientProfile


class StockItemStatus(models.TextChoices):
    EN_ATTENTE = 'EN_ATTENTE', 'En attente'
    APPROUVE = 'APPROUVE', 'Approuvé'
    REFUSE = 'REFUSE', 'Refusé'


def generate_reference() -> str:
    return uuid.uuid4().hex[:8].upper()


class StockItem(models.Model):
    reference = models.CharField(max_length=20, unique=True, default=generate_reference)
    client = models.ForeignKey(ClientProfile, on_delete=models.CASCADE, related_name='stock_items')
    quantity = models.PositiveIntegerField()
    description = models.TextField(blank=True)
    photo = models.ImageField(upload_to='stock/', null=True, blank=True)
    status = models.CharField(max_length=15, choices=StockItemStatus.choices, default=StockItemStatus.EN_ATTENTE)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self) -> str:
        return f"{self.reference} ({self.status})"