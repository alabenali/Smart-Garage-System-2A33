$phpPath = "C:\xampp\php\php.exe"
$scriptPath = "C:\xampp\htdocs\samrtnour\samrt nour\cron\send_garantie_alerts.php"

# Vérifier si PHP existe à l'emplacement par défaut de XAMPP
if (-Not (Test-Path $phpPath)) {
    Write-Host "ATTENTION : php.exe introuvable dans $phpPath" -ForegroundColor Red
    Write-Host "Veuillez modifier la variable `$phpPath dans ce script avec le bon chemin." -ForegroundColor Yellow
    Exit
}

# Définir l'action (Exécuter php.exe avec le script cron en argument)
$action = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$scriptPath`""

# Définir le déclencheur (Tous les jours à 09:00)
$trigger = New-ScheduledTaskTrigger -Daily -At 9am

# Optionnel : définir les paramètres pour exécuter indépendamment si l'utilisateur est connecté ou non
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Nom de la tâche
$taskName = "SmartGarage_GarantiesAlerts"

# Supprimer l'ancienne tâche si elle existe déjà pour la mettre à jour
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Host "Ancienne tâche supprimée." -ForegroundColor Yellow
}

# Créer et enregistrer la tâche dans le planificateur Windows
Register-ScheduledTask -Action $action -Trigger $trigger -Settings $settings -TaskName $taskName -Description "Envoi automatique quotidien des alertes SMS pour les garanties Smart Garage." -User "NT AUTHORITY\SYSTEM" -RunLevel Highest

Write-Host "======================================================" -ForegroundColor Green
Write-Host "SUCCES : La tache planifiee '$taskName' a ete creee." -ForegroundColor Green
Write-Host "Elle s'executera tous les jours en arriere-plan a 09:00." -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Green
Read-Host "Appuyez sur Entree pour quitter"
