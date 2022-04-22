<style>
  .navbar-nav {
    font-size: 1.1rem;
  }
  </style>
<?php 
require_once 'utils.php';
//pa($_SERVER);

if($user->isLoggedIn()): ?>
  <?php
  if(!empty($user->data['org_name'])) print("
<div class='bg-dark text-light'><strong class='ml-2'>".$user->current_org_name()."</strong></div>
          ");
          ?>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
<span class="navbar-brand"><a href='<?php print(URL_SUPPORT);?>'><img src="<?php print(ROOT_URI);?>images/logo.png" width="50" height="50" alt="" title="MUSA"></a></span>
    <div class="d-flex flex-row order-2 order-lg-3">
      <ul class="navbar-nav flex-row">
      <?php if($user->can(['manual'],false)): ?>
      <li class="nav-item">
        <a class="nav-link mr-2" href="<?php print(ROOT_URI);?>manual.pdf" title="Manual"><i class="fa fa-question"></i></a>
      </li>
      <?php endif?>
      <li class="nav-item"><a class="nav-link mr-2" href="<?php print(ROOT_URI);?>#" title="Startsida"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle " href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fa fa-user"></i> 
          <?php if(!empty($user->data['name'])) print($user->data['name']);?>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
        <?php 
        if(!empty($user->data['picture'])) print('<a class="dropdown-item" href="'.ROOT_URI.'member_account.php"><img src="'.$user->data['picture'].'" width="100"  alt=""></a>');
        if(!empty($user->data['name'])) print("
          <div class='dropdown-item'><strong>".$user->data['name']."</strong></div>
          <div class='dropdown-divider'></div>
          ");
        if(!empty($user->data['org_name'])) print("
          <div class='dropdown-item'><strong>".$user->data['org_name']."</strong></div>
          <div class='dropdown-divider'></div>
          ");
        if(!empty($user->data['role_name'])) print("
          <div class='dropdown-item'><strong>".$user->data['role_name']."</strong></div>
          <div class='dropdown-divider'></div>
          ");
        ?> 
          <a class="dropdown-item" href="<?php print(ROOT_URI);?>member_account.php"><i class="fa fa-user"></i> Konto</a>
          <a class="dropdown-item" href="<?php print(ROOT_URI);?>notify_settings.php"><i class="fa fa-cog"></i> Inställningar</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="<?php print($_SERVER['REQUEST_URI']."?signout");?>"><i class="fas fa-sign-out-alt"></i> Logga ut</a>
        </div>
      </li>
  
      </ul>
      <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon">
      </button>
    </div>  

<div class="collapse navbar-collapse" id="navbarSupportedContent">
  <ul class="navbar-nav mr-auto"> 
  <?php if($user->can(['admin','search'],false)): ?>
    <li class="nav-item">
      <a class="nav-link" href="<?php print(ROOT_URI."music_search.php");?>">Söka</a>
    </li>
  <?php endif?>
  <?php if($user->can(['admin','edit'],false)): ?>
    <li class="nav-item">
      <a class="nav-link" href="<?php print(ROOT_URI."edit.php");?>">Editera</a>
    </li>
  <?php endif?>
<?php if($user->can('admin')): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Admin
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>user_invite.php">Bjud in ny användare</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>users_admin.php">Hantera användare</a>
    </div>
  </li>
<?php endif?>
<?php if($user->can(['ekonomi'])): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Licens
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>economy_licence.php">Licens</a>
      <div class="dropdown-divider"></div>
    </div>
  </li>
<?php endif?>
<?php if($user->can('system')): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      System
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>orgs_admin.php">Organisationer</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>admins_admin.php">Administratörer</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>orgs_admin.php?recover">Dolda Organisationer</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>users_delete.php">Radera användare</a>
      <div class="dropdown-divider"></div>
      <?php 
      if($user->can('settings')) {
        print('
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="'.ROOT_URI.'roletypes_admin.php">Rolltyper</a>
        <a class="dropdown-item" href="'.ROOT_URI.'permissiontypes_admin.php">Rättigheter</a>
        ');
        print('<a class="dropdown-item" href="'.ROOT_URI.'system_settings.php">Inställningar</a>');
      }

        print('<a class="dropdown-item" href="'.ROOT_URI.'system_log.php">Log</a>');
        print('<a class="dropdown-item" href="https://analytics.google.com/analytics/web/?authuser=0#/report/trafficsources-overview/a177007171/">Google Analytics</a>');
     ?>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>maintenance_cron.php">Kör Underhåll</a>

    </div>
  </li>
<?php endif?>
    </ul>
  </div>

</nav>
</br>

<?php endif?>