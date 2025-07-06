from rest_framework.routers import DefaultRouter

from .views import PickupOrderViewSet

router = DefaultRouter()
router.register('orders', PickupOrderViewSet, basename='pickup-order')

urlpatterns = router.urls