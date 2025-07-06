from __future__ import annotations

from django.db import models


class City(models.Model):
    name = models.CharField(max_length=100, unique=True)

    def __str__(self) -> str:
        return self.name


class Zone(models.Model):
    name = models.CharField(max_length=100, unique=True)
    cities = models.ManyToManyField(City, related_name='zones')

    def __str__(self) -> str:
        return self.name


class Tariff(models.Model):
    city = models.ForeignKey(City, on_delete=models.CASCADE, related_name='tariffs')
    delivery_price = models.DecimalField(max_digits=8, decimal_places=2)
    refusal_price = models.DecimalField(max_digits=8, decimal_places=2)
    return_price = models.DecimalField(max_digits=8, decimal_places=2)
    standard_delivery_time = models.PositiveIntegerField(help_text='In days')

    def __str__(self) -> str:
        return f"Tariff for {self.city.name}"