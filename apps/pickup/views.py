from rest_framework import permissions, viewsets

from .models import PickupOrder, PickupStatus
from .serializers import PickupOrderSerializer


class IsClient(permissions.BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role == 'CLIENT'


class PickupOrderViewSet(viewsets.ModelViewSet):
    serializer_class = PickupOrderSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        user = self.request.user
        if user.role == 'ADMIN':
            return PickupOrder.objects.all()
        elif user.role == 'CLIENT':
            return PickupOrder.objects.filter(client=user.client_profile)
        return PickupOrder.objects.none()

    def perform_create(self, serializer):
        serializer.save(client=self.request.user.client_profile)