<?php require 'header.php' ?>

<!-- Combined profile update and image upload form -->
<form class="profile-form" id="profileForm" action="/profile/update" method="POST" enctype="multipart/form-data">
    <!-- Hidden input -->
    <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?? ''; ?>">
    <input type="hidden" name="image_name" value="<?= $profile['image_name'] ?? ''; ?>">
    <input type="hidden" name="image_url" value="<?= $profile['image_url'] ?? ''; ?>">
    <input type="hidden" name="image_id" value="<?= $profile['image_id'] ?? ''; ?>">
    
    <div class="profile-image-column">
        <!-- Profile image -->
        <img id="profileImagePreview" src="<?= $profile['image_url'] ?? 'https://placehold.co/400?text=Profile+Picture'; ?>" alt="<?= $profile['image_name'] ?? 'placeholder' ?>">

        <!-- Upload image -->
        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;">
        <button type="button" onclick="document.getElementById('profile_image').click();">Upload</button><br>
    </div>

    <div class="profile-info-column">
        <div class="profile-info-row">
            <!-- First name label -->
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?= $profile['first_name'] ?? ''; ?>" required><br>
                <?php if (isset($error['first_name'])): ?>
                    <p><?= htmlspecialchars($error['first_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <br>
                <?php endif; ?>
            </div>
        
            <!-- Last name label -->
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?= $profile['last_name'] ?? ''; ?>" required><br>
                <?php if (isset($error['last_name'])): ?>
                    <p><?= htmlspecialchars($error['last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <br>
                <?php endif; ?>
            </div>
        
            <!-- Birthdate label -->
            <div class="form-group">
                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" value="<?= $profile['birthdate'] ?? ''; ?>" required><br>
                <?php if (isset($error['birthdate'])): ?>
                    <p><?= htmlspecialchars($error['birthdate'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <br>
                <?php endif ?>
            </div>
        </div>

        <div class="form-group" style="height: 100%;">
            <!-- Description label -->
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?= $profile['description'] ?? ''; ?></textarea><br>
            <?php if (isset($error['description'])): ?>
                <p><?= htmlspecialchars($error['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                <br>
            <?php endif; ?>
        </div>
        
        <!-- Save button -->
        <button class="save-button" type="submit">Save</button>
    </div>
</form>

<script>
    // JavaScript to handle image preview
    document.getElementById('profile_image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php require 'footer.php' ?>