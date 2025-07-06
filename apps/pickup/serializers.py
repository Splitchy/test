from rest_framework import serializers

from .models import PickupOrder


class PickupOrderSerializer(serializers.ModelSerializer):
    class Meta:
        model = PickupOrder
        fields = '__all__'
        read_only_fields = ('id', 'tracking_code', 'status', 'client', 'created_at', 'updated_at')