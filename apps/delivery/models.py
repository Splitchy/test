from __future__ import annotations

import datetime
import random
from django.db import models

from apps.pickup.models import PickupOrder
from apps.users.models import ClientProfile, LivreurProfile


class BLStatus(models.TextChoices):
    EN_PREPARATION = 'EN_PREPARATION', 'En préparation'
    RECU = 'RECU', 'Reçu'


class BDStatus(models.TextChoices):
    PRET_POUR_DISTRIBUTION = 'PRET_POUR_DISTRIBUTION', 'Prêt pour distribution'
    RECU = 'RECU', 'Reçu'


def generate_bl_code() -> str:
    today = datetime.date.today()
    seq = random.randint(1000, 9999)
    return f"BL{today:%Y%m%d}{seq}"


def generate_bd_code() -> str:
    today = datetime.date.today()
    seq = random.randint(1000, 9999)
    return f"BD{today:%Y%m%d}{seq}"


class BonLivraison(models.Model):
    code = models.CharField(max_length=20, unique=True, default=generate_bl_code)
    client = models.ForeignKey(ClientProfile, on_delete=models.CASCADE, related_name='bons_livraison')
    orders = models.ManyToManyField(PickupOrder, related_name='bon_livraison')
    status = models.CharField(max_length=20, choices=BLStatus.choices, default=BLStatus.EN_PREPARATION)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.code


class BonDistribution(models.Model):
    code = models.CharField(max_length=20, unique=True, default=generate_bd_code)
    livreur = models.ForeignKey(LivreurProfile, on_delete=models.CASCADE, related_name='bons_distribution')
    orders = models.ManyToManyField(PickupOrder, related_name='bon_distribution')
    status = models.CharField(max_length=25, choices=BDStatus.choices, default=BDStatus.PRET_POUR_DISTRIBUTION)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.code