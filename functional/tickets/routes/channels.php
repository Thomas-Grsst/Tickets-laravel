<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.tickets', fn ($user, int $userId): bool => (int) $user->id === $userId);
