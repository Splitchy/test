from rest_framework import serializers

from .models import City, Zone, Tariff


class CitySerializer(serializers.ModelSerializer):
    class Meta:
        model = City
        fields = '__all__'


class ZoneSerializer(serializers.ModelSerializer):
    cities = CitySerializer(many=True, read_only=True)

    class Meta:
        model = Zone
        fields = '__all__'


class TariffSerializer(serializers.ModelSerializer):
    city = CitySerializer(read_only=True)
    city_id = serializers.PrimaryKeyRelatedField(source='city', queryset=City.objects.all(), write_only=True)

    class Meta:
        model = Tariff
        fields = ('id', 'city', 'city_id', 'delivery_price', 'refusal_price', 'return_price', 'standard_delivery_time')