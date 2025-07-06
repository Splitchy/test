from rest_framework import permissions, status, views, viewsets
from rest_framework.decorators import action
from rest_framework.response import Response

from apps.pickup.models import PickupOrder, PickupStatus

from .models import BonLivraison, BonDistribution, BLStatus, BDStatus
from .serializers import BonLivraisonSerializer, BonDistributionSerializer


class BonLivraisonViewSet(viewsets.ModelViewSet):
    queryset = BonLivraison.objects.all()
    serializer_class = BonLivraisonSerializer
    permission_classes = [permissions.IsAuthenticated]

    def create(self, request, *args, **kwargs):
        order_ids = request.data.get('order_ids', [])
        if not order_ids:
            return Response({'detail': 'order_ids required'}, status=status.HTTP_400_BAD_REQUEST)
        orders = PickupOrder.objects.filter(id__in=order_ids, status__in=[PickupStatus.EN_ATTENTE_RAMASSAGE, PickupStatus.EN_PREPARATION])
        if not orders:
            return Response({'detail': 'No eligible orders'}, status=status.HTTP_400_BAD_REQUEST)
        bl = BonLivraison.objects.create(client=request.user.client_profile)
        bl.orders.set(orders)
        orders.update(status=PickupStatus.EN_PREPARATION)
        serializer = self.get_serializer(bl)
        return Response(serializer.data, status=status.HTTP_201_CREATED)

    @action(detail=True, methods=['post'])
    def scan(self, request, pk=None):
        bl = self.get_object()
        code = request.data.get('tracking_code')
        try:
            order = bl.orders.get(tracking_code=code)
        except PickupOrder.DoesNotExist:
            return Response({'detail': 'Not found'}, status=status.HTTP_404_NOT_FOUND)
        if order.status == PickupStatus.RAMASSE:
            return Response({'detail': 'Duplicate'}, status=status.HTTP_409_CONFLICT)
        order.status = PickupStatus.RAMASSE
        order.save()
        return Response({'detail': 'Scanned', 'order_id': order.id})

    @action(detail=True, methods=['post'])
    def close(self, request, pk=None):
        bl = self.get_object()
        if bl.orders.filter(status__in=[PickupStatus.EN_PREPARATION, PickupStatus.EN_ATTENTE_RAMASSAGE]).exists():
            return Response({'detail': 'Some orders not yet scanned'}, status=status.HTTP_400_BAD_REQUEST)
        bl.status = BLStatus.RECU
        bl.save()
        return Response(self.get_serializer(bl).data)


class BonDistributionViewSet(viewsets.ModelViewSet):
    queryset = BonDistribution.objects.all()
    serializer_class = BonDistributionSerializer
    permission_classes = [permissions.IsAuthenticated]

    def create(self, request, *args, **kwargs):
        order_ids = request.data.get('order_ids', [])
        livreur_id = request.data.get('livreur_id')
        if not order_ids or not livreur_id:
            return Response({'detail': 'order_ids and livreur_id required'}, status=status.HTTP_400_BAD_REQUEST)
        orders = PickupOrder.objects.filter(id__in=order_ids, status=PickupStatus.RAMASSE)
        if not orders:
            return Response({'detail': 'No eligible orders'}, status=status.HTTP_400_BAD_REQUEST)
        from apps.users.models import LivreurProfile  # local import to avoid circular
        try:
            livreur = LivreurProfile.objects.get(id=livreur_id)
        except LivreurProfile.DoesNotExist:
            return Response({'detail': 'Livreur not found'}, status=status.HTTP_404_NOT_FOUND)
        bd = BonDistribution.objects.create(livreur=livreur)
        bd.orders.set(orders)
        orders.update(status=PickupStatus.MISE_EN_DISTRIBUTION)
        serializer = self.get_serializer(bd)
        return Response(serializer.data, status=status.HTTP_201_CREATED)

    @action(detail=True, methods=['post'])
    def scan(self, request, pk=None):
        bd = self.get_object()
        code = request.data.get('tracking_code')
        try:
            order = bd.orders.get(tracking_code=code)
        except PickupOrder.DoesNotExist:
            return Response({'detail': 'Not found'}, status=status.HTTP_404_NOT_FOUND)
        if order.status in [PickupStatus.LIVRE, PickupStatus.REFUSE, PickupStatus.ANNULE]:
            return Response({'detail': 'Duplicate'}, status=status.HTTP_409_CONFLICT)
        result = request.data.get('result', 'LIVRE')
        if result not in [PickupStatus.LIVRE, PickupStatus.REFUSE, PickupStatus.ANNULE]:
            return Response({'detail': 'Invalid result'}, status=status.HTTP_400_BAD_REQUEST)
        order.status = result
        order.save()
        return Response({'detail': 'Updated', 'order_id': order.id})

    @action(detail=True, methods=['post'])
    def close(self, request, pk=None):
        bd = self.get_object()
        if bd.orders.filter(status=PickupStatus.MISE_EN_DISTRIBUTION).exists():
            return Response({'detail': 'Some orders not yet delivered'}, status=status.HTTP_400_BAD_REQUEST)
        bd.status = BDStatus.RECU
        bd.save()
        return Response(self.get_serializer(bd).data)