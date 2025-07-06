"""URL configuration for core project."""
from django.contrib import admin
from django.urls import include, path
from drf_spectacular.views import SpectacularAPIView, SpectacularSwaggerView

urlpatterns = [
    path('admin/', admin.site.urls),
    # API schema
    path('api/schema/', SpectacularAPIView.as_view(), name='schema'),
    path('api/schema/swagger/', SpectacularSwaggerView.as_view(url_name='schema'), name='swagger-ui'),

    # Apps
    path('api/auth/', include('apps.users.urls')),
    path('api/geo/', include('apps.geo.urls')),
    path('api/stock/', include('apps.stock.urls')),
    path('api/pickup/', include('apps.pickup.urls')),
    path('api/delivery/', include('apps.delivery.urls')),
    path('api/invoicing/', include('apps.invoicing.urls')),
]