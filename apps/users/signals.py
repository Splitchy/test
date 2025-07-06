from django.db.models.signals import post_save
from django.dispatch import receiver

from .models import CustomUser, Roles, ClientProfile, LivreurProfile


@receiver(post_save, sender=CustomUser)
def create_role_profile(sender, instance: CustomUser, created: bool, **kwargs):
    if not created:
        return

    if instance.role == Roles.CLIENT:
        ClientProfile.objects.create(user=instance, first_name=instance.first_name or '', last_name=instance.last_name or '', cin='')
    elif instance.role == Roles.LIVREUR:
        LivreurProfile.objects.create(user=instance, first_name=instance.first_name or '', last_name=instance.last_name or '')