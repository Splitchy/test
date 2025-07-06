from django.contrib import admin
from django.contrib.auth.admin import UserAdmin

from .models import CustomUser, ClientProfile, LivreurProfile


@admin.register(CustomUser)
class CustomUserAdmin(UserAdmin):
    model = CustomUser
    list_display = ('id', 'email', 'role', 'is_approved', 'is_staff')
    list_filter = ('role', 'is_approved')
    ordering = ('id',)
    search_fields = ('email',)
    fieldsets = (
        (None, {'fields': ('email', 'password', 'role', 'is_approved')}),
        ('Permissions', {'fields': ('is_staff', 'is_superuser')}),
        ('Dates', {'fields': ('last_login', 'date_joined')}),
    )
    add_fieldsets = (
        (None, {
            'classes': ('wide',),
            'fields': ('email', 'password1', 'password2', 'role', 'is_approved')
        }),
    )


admin.site.register(ClientProfile)
admin.site.register(LivreurProfile)