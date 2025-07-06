from rest_framework.routers import DefaultRouter

from .views import StockItemViewSet

router = DefaultRouter()
router.register('items', StockItemViewSet, basename='stock-item')

urlpatterns = router.urls