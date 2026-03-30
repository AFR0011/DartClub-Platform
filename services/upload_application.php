<?php

if (isset($_FILES['forms']) && !isset($_FILES['form'])) {
    $_FILES['form'] = $_FILES['forms'];
}

require_once __DIR__ . '/submit_membership_application.php';

