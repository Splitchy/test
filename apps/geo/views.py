from rest_framework import permissions, viewsets

from .models import City, Zone, Tariff
from .serializers import CitySerializer, ZoneSerializer, TariffSerializer


class IsAdminOrReadOnly(permissions.BasePermission):
    def has_permission(self, request, view):
        if request.method in permissions.SAFE_METHODS:
            return True
        return request.user.is_authenticated and request.user.role == 'ADMIN'


class CityViewSet(viewsets.ModelViewSet):
    queryset = City.objects.all()
    serializer_class = CitySerializer
    permission_classes = [IsAdminOrReadOnly]


class ZoneViewSet(viewsets.ModelViewSet):
    queryset = Zone.objects.all()
    serializer_class = ZoneSerializer
    permission_classes = [IsAdminOrReadOnly]


class TariffViewSet(viewsets.ModelViewSet):
    queryset = Tariff.objects.select_related('city').all()
    serializer_class = TariffSerializer
    permission_classes = [IsAdminOrReadOnly]