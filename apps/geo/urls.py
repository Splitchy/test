from rest_framework.routers import DefaultRouter

from .views import CityViewSet, ZoneViewSet, TariffViewSet

router = DefaultRouter()
router.register('cities', CityViewSet)
router.register('zones', ZoneViewSet)
router.register('tariffs', TariffViewSet)

urlpatterns = router.urls