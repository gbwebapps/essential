<div class="card-body">
    <div>
        <p class="lead"><i class="fa-solid fa-user"></i> <span class="fw-bold"><?= esc($currentAdmin->firstname) . ' ' . esc($currentAdmin->lastname) ?> </span></p>
        <p class="lead"><i class="fa-solid fa-envelope"></i> <span class="fw-bold"><?= esc($currentAdmin->email); ?> </span></p>
        <p class="lead"><i class="fa-solid fa-phone"></i> <span class="fw-bold"><?= esc($currentAdmin->phone); ?> </span></p>
    </div>
</div>