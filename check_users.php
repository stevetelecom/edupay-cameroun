<?php
$emails = ["directeur.douala@test.cm", "etudiant@test.cm"];
foreach ($emails as $email) {
    $u = \App\Models\User::withTrashed()->where("email", $email)->first();
    if ($u === null) {
        echo $email . " : INTROUVABLE" . PHP_EOL;
        continue;
    }
    $deleted = $u->deleted_at ? $u->deleted_at->format("Y-m-d H:i:s") : "NON";
    $check = Hash::check("password", $u->password) ? "OK" : "FAUX";
    echo $email . " - id:" . $u->id . " - deleted_at:" . $deleted . " - hash_check(password):" . $check . PHP_EOL;
}
