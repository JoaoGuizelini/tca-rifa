<?php

use App\Models\Role;

// Initial grants. Runtime authorization reads the database, not this array.
$common = ['account.show', 'category.index', 'campaign.index', 'campaign.show',
    'organizer.show', 'campaign.photo', 'area.index', 'studio.index'];
$participation = ['reservation.store', 'reservation.confirm'];

return [
    Role::PARTICIPANT => [...$common, ...$participation, 'organizer.store'],
    Role::ORGANIZER => [...$common, ...$participation, 'campaign.store', 'campaign.update', 'campaign.change'],
    Role::ADMINISTRATOR => [...$common, 'admin.index', 'organizer.document', 'organizer.decide', 'campaign.decide',
        'user.suspend', 'category.store', 'category.update', 'category.destroy', 'campaign.viewPrivate'],
];
