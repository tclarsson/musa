<script type="text/javascript">
  function wipAlert() {
    $(".disabled").click(function(event){
      alert("Tyvärr är sidan/länken under utveckling och inte tillgänglig just nu");
      event.preventDefault();
    });
  }
  window.onload = wipAlert;
</script>
<style>
  .navbar-nav {
    font-size: 1.1rem;
  }
  </style>
<?php 
require_once 'utils.php';
//pa($_SERVER);

if(!empty($_SESSION['unread_info'])) $unread_info="<span class='badge badge-pill badge-info'>$_SESSION[unread_info]</span>"; else $unread_info="";
if(!empty($_SESSION['ladder_challenges'])) $ladder_challenges="<span class='badge badge-pill badge-danger'>$_SESSION[ladder_challenges]</span>"; else $ladder_challenges="";
if(!empty($_SESSION['unread_ladder'])) $unread_ladder="<span class='badge badge-pill badge-info'>$_SESSION[unread_ladder]</span>"; else $unread_ladder="";

if(!empty($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
<a class="navbar-brand" href="https://www.aspotennisklubb.se/" title="Hemsida"><img src="<?php print(ROOT_URI);?>images/atklogo.webp" width="70" height="50" alt=""></a>
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
            <?php if(!empty($_SESSION['name'])) print($_SESSION['name']);?>
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <?php if(!empty($user_data['picture'])) 
            print('<a class="dropdown-item" href="'.ROOT_URI.'member_account.php"><img src="'.$user_data['picture'].'" width="100"  alt=""></a>');
          ?>
          
            <div class="dropdown-item"><strong><?php if(!empty($_SESSION['ROLE'])) print($_SESSION['ROLE']); else print('-');?></strong></div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php print(ROOT_URI);?>member_account.php#saldo"><i class="fas fa-piggy-bank"></i> Saldo</a>
            <a class="dropdown-item" href="<?php print(ROOT_URI);?>member_account.php"><i class="fa fa-user"></i> Konto</a>
            <a class="dropdown-item" href="<?php print(ROOT_URI);?>notify_settings.php"><i class="fa fa-cog"></i> Inställningar</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php print($_SERVER['REQUEST_URI']."?signout");?>"><i class="fas fa-sign-out-alt"></i> Logga ut</a>
          </div>
        </li>
  
      </ul>
      <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span><?php print($unread_info);?><?php print($ladder_challenges);?>
      </button>
    </div>  

<div class="collapse navbar-collapse" id="navbarSupportedContent">
  <ul class="navbar-nav mr-auto"> 
  <?php if($user->can(['admin','anmäla'],false)): ?>
    <li class="nav-item">
      <a class="nav-link" href="<?php print(ROOT_URI."home.php");?>#activeEvents">Anmälan</a>
    </li>
  <?php endif?>
  <?php if($user->can(['admin','anmäla'],false)): ?>
    <li class="nav-item">
      <a class="nav-link" href="<?php print(ROOT_URI."ladder.php");?>">Stegen <?php print($ladder_challenges);?><?php print($unread_ladder);?></a>
    </li>
  <?php endif?>
<?php if($user->can(['admin','kontakt'],false)): ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Info <?php print($unread_info);?>
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>infoboard.php"><i class="fas fa-sticky-note"></i> Anslagstavlan <?php print($unread_info);?></a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>info_bookcourt.php"><i class="far fa-calendar-plus"></i> Boka banorna</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>info_membership.php"><i class="fas fa-users"></i> Medlemskap</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>about.php"><i class="fa fa-question"></i> Medlemsservice</a>
    </div>
    </li>
<?php endif?>
<?php if($user->can(['admin','kontakt'],false)): ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Kontakt
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>contact_members.php"><i class="fas fa-users"></i> Medlemslista</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>contact_officials.php"><i class="fas fa-users-cog"></i> Funktionärer</a>
    <div class="dropdown-divider"></div>
    <a href="mailto:<?php print(EMAIL_CONTACT);?>?subject=ATK Kontakt" class="btn btn-primary ml-2 mr-2" title='Skicka ett meddelande till klubben'><i class="fas fa-envelope"></i> Kontakta klubben</a>
    </div>
    </li>
<?php endif?>
<?php if($user->can(['admin','rapporter'],false)): ?>
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Rapporter
  </a>
  <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>events_reports.php">Aktivitetsrapporter</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>list_members.php">Medlemmar</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>list_families.php">Familjeansvariga</a>
    
  </div>
</li>
<?php endif?>
<?php if($user->can('admin')): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Admin
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <!--
    <a class="dropdown-item disabled" href="<?php print(ROOT_URI);?>messaging.php">Mailutskick</a>
    <a class="dropdown-item" href="https://analytics.google.com/analytics/web/?authuser=0#/report/trafficsources-overview/a177007171/">Google Analytics</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>members_deactivate.php">Deaktivera medlemmar</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>membership_management.php">Hantera Medlemskap</a>
    -->
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>members_verify.php">Godkänn nya medlemmar</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>invite_member.php">Bjud in ny medlem</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>members_admin.php">Hantera Medlemsdata</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>families_admin.php">Hantera Familjer</a>
    <a class="dropdown-item" href="<?php print(ROOT_URI);?>members_reactivate.php">Återaktivera medlemmar</a>
    </div>
  </li>
<?php endif?>
<?php if($user->can(['admin','aktiviteter'],false)): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Aktiviteter
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>events_admin.php">Hantera Aktiviteter</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>categorytypes_admin.php">Klasstyper</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>eventtypes_admin.php">Aktivitetstyper</a>
    </div>
  </li>
<?php endif?>
<?php if($user->can(['ekonomi'])): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Ekonomi
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>economy_registration.php">Inbetalning</a>
      <div class="dropdown-divider"></div>
            
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>economy_balance.php">Saldo</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>economy_transactions.php">Transaktioner</a>
    </div>
  </li>
<?php endif?>
<?php if($user->can('system')): ?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      System
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>members_delete.php">Radera medlemmar</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>membershiptypes_admin.php">Medlemskapstyper</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>infoboardtypes_admin.php">Anslagstyper</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>leveltypes_admin.php">Spelnivåtyper</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>functiontypes_admin.php">Funktionärer</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>admins_admin.php">Administratörer</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>roletypes_admin.php">Rolltyper</a>
      <a class="dropdown-item" href="<?php print(ROOT_URI);?>permissiontypes_admin.php">Rättigheter</a>
      <div class="dropdown-divider"></div>
      <?php if($user->can('settings')) 
        print('<a class="dropdown-item" href="'.ROOT_URI.'system_log.php">Log</a>');
        print('<a class="dropdown-item" href="'.ROOT_URI.'system_settings.php">Inställningar</a>');
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

<?php else:
// ----------------------------------------------------------------------------------------------------
// inte inloggad, inte medlem
// ----------------------------------------------------------------------------------------------------
?>
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
<a class="navbar-brand" href="https://www.aspotennisklubb.se/" title="Hemsida"><img src="<?php print(ROOT_URI);?>images/atklogo.webp" width="70" height="50" alt=""></a>
    <div class="d-flex flex-row order-2 order-lg-3">
      <ul class="navbar-nav flex-row">
      <li class="nav-item"><a class="nav-link mr-2" href="<?php print($_SERVER['REQUEST_URI']."?signin");?>" title="Logga in"><i class="fas fa-sign-in-alt fa-lg"></i> Logga in </a></li>
      </ul>
      <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
      </button>
    </div>  

<div class="collapse navbar-collapse" id="navbarSupportedContent">
  <ul class="navbar-nav mr-auto"> 
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#"  title="Information från klubben" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-info-circle"></i>
        Info
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
        <a class="dropdown-item" href="<?php print(ROOT_URI);?>infoboard.php"><i class="fas fa-sticky-note"></i> Anslagstavlan</a>
        <a class="dropdown-item" href="<?php print(ROOT_URI);?>info_bookcourt.php"><i class="far fa-calendar-plus"></i> Boka banorna</a>
        <a class="dropdown-item" href="<?php print(ROOT_URI);?>info_membership.php"><i class="fas fa-users"></i> Medlemskap</a>
        </div>
    </li>

    <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="<?php print(ROOT_URI);?>#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-envelope"></i>
    Kontakt
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <a href="mailto:<?php print(EMAIL_CONTACT);?>?subject=ATK Kontakt" class="btn btn-primary ml-2 mr-2" title='Skicka ett meddelande till klubben'><i class="fas fa-envelope"></i> Kontakta klubben</a>
    </div>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php print(ROOT_URI."signup.php");?>#"  title="Registrera medlemskap"><i class="fas fa-users"></i> Bli medlem</a>
      </li>

    </ul>
</nav>

<?php endif?>