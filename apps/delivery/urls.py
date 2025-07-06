from rest_framework.routers import DefaultRouter

from .views import BonLivraisonViewSet, BonDistributionViewSet

router = DefaultRouter()
router.register('bon-livraison', BonLivraisonViewSet, basename='bon-livraison')
router.register('bon-distribution', BonDistributionViewSet, basename='bon-distribution')

urlpatterns = router.urls