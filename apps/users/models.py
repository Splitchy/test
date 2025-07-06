from __future__ import annotations

from django.contrib.auth.models import AbstractUser
from django.db import models
from django.utils.translation import gettext_lazy as _


class Roles(models.TextChoices):
    ADMIN = 'ADMIN', _('Admin')
    CLIENT = 'CLIENT', _('Client')
    LIVREUR = 'LIVREUR', _('Livreur')


class CustomUser(AbstractUser):
    email = models.EmailField(_('email address'), unique=True)
    role = models.CharField(max_length=10, choices=Roles.choices, default=Roles.CLIENT)
    is_approved = models.BooleanField(default=False)

    USERNAME_FIELD = 'email'
    REQUIRED_FIELDS = ['username']  # keeps username for compatibility

    def __str__(self) -> str:  # noqa: D401
        return f"{self.email} ({self.role})"


class ClientProfile(models.Model):
    user = models.OneToOneField(CustomUser, on_delete=models.CASCADE, related_name='client_profile')
    cin = models.CharField(max_length=20)
    first_name = models.CharField(max_length=100)
    last_name = models.CharField(max_length=100)
    store_name = models.CharField(max_length=255)
    bank_info = models.CharField(max_length=255)
    stock_enabled = models.BooleanField(default=True)

    def __str__(self) -> str:  # noqa: D401
        return f"Client {self.store_name}"


class LivreurProfile(models.Model):
    user = models.OneToOneField(CustomUser, on_delete=models.CASCADE, related_name='livreur_profile')
    first_name = models.CharField(max_length=100)
    last_name = models.CharField(max_length=100)
    delivery_fee = models.DecimalField(max_digits=8, decimal_places=2, default=0)
    refusal_fee = models.DecimalField(max_digits=8, decimal_places=2, default=0)
    bank_info = models.CharField(max_length=255)
    cin_front = models.ImageField(upload_to='cin/', null=True, blank=True)
    cin_back = models.ImageField(upload_to='cin/', null=True, blank=True)
    rib = models.ImageField(upload_to='rib/', null=True, blank=True)

    def __str__(self) -> str:  # noqa: D401
        return f"Livreur {self.first_name} {self.last_name}"