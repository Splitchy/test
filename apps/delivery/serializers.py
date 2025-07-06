from rest_framework import serializers

from .models import BonLivraison, BonDistribution


class BonLivraisonSerializer(serializers.ModelSerializer):
    class Meta:
        model = BonLivraison
        fields = '__all__'


class BonDistributionSerializer(serializers.ModelSerializer):
    class Meta:
        model = BonDistribution
        fields = '__all__'