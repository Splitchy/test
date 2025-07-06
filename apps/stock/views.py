from rest_framework import permissions, viewsets, status
from rest_framework.decorators import action
from rest_framework.response import Response

from .models import StockItem, StockItemStatus
from .serializers import StockItemSerializer


class IsClient(permissions.BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role == 'CLIENT'


class IsAdmin(permissions.BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role == 'ADMIN'


class StockItemViewSet(viewsets.ModelViewSet):
    serializer_class = StockItemSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        user = self.request.user
        if user.role == 'ADMIN':
            return StockItem.objects.all()
        elif user.role == 'CLIENT':
            return StockItem.objects.filter(client=user.client_profile)
        return StockItem.objects.none()

    def perform_create(self, serializer):
        user = self.request.user
        serializer.save(client=user.client_profile)

    @action(detail=True, methods=['post'], permission_classes=[IsAdmin])
    def approve(self, request, pk=None):
        item = self.get_object()
        item.status = StockItemStatus.APPROUVE
        item.save()
        return Response(self.get_serializer(item).data)

    @action(detail=True, methods=['post'], permission_classes=[IsAdmin])
    def refuse(self, request, pk=None):
        item = self.get_object()
        item.status = StockItemStatus.REFUSE
        item.save()
        return Response(self.get_serializer(item).data)