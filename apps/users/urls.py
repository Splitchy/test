from django.urls import path

from .views import LoginView, RegisterView, ApproveUserView

urlpatterns = [
    path('register/', RegisterView.as_view(), name='register'),
    path('login/', LoginView.as_view(), name='login'),
    path('approve-user/<int:pk>/', ApproveUserView.as_view(), name='approve-user'),
]