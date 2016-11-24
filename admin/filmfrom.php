<?php
# Filmkatalog, Website mit Verbindung zur MySQL-Datenbank
# Formular zum Einfügen und bearbeiten von Filmen
#
# basierien auf einem Layout von Michael Hassel(hassel@mediakontur.de)
#
# Autor: Andreas Beer
# Email: andreasbeer@gmx.com
# Stand: 19.09.2016
# Version: Basisversion für Schulungszwecke
?>

<?php
include_once '../inc/config.inc.php';
include_once '../inc/dbconn.php';
include_once '../inc/functions.inc.php';
?>

<?php

function getValue ($name, $default = '') {
    return !empty($_POST[$name]) ? $_POST[$name] : $default;
}

function getError ($name, $default = '') {
    
    global $msgErrors;
    
    if(isset($msgErrors[$name])) {
        echo $msgErrors[$name];
    } else {
        echo $default;
    }
}

// Die Variablen mit defaultwert

$id          = is_numeric($_GET['f']) ? $_GET['f'] : false;
$company_id  = getValue('fc');
$genre_id    = getValue('fg');
$title       = getValue('ft');
$desc        = getValue('dc');
$date        = getValue('dt');
$duration    = getValue('du');
$price       = getValue('pr');
$image       = getValue('img');
$film_id     = getValue('fid');     

/*
 * Problem:
 * Wenn der film NICHT für die Öffentlichkeit zu sehen ist,
 * ist der Wert leer.
 * Wir übersetzen diesen leeren Wert in einen Null-String,
 * damit beim Eintrag in die Datenbank, eine Null gesetzt wird.
 */
$visible     = getValue('vi', '0');

$msg_btn  = 'Speichern';

if ($id) {
    
    $handler_film = mysqli_query($conn, $sql_select_moviesBymovieId($id));
    $data = mysqli_fetch_assoc($handler_film);

    $company_id = $data['Filmgesellschaft_id'];
    $genre_id   = $data['Genre_id'];
    $title      = $data['Titel'];
    $desc       = $data['Beschreibung'];
    $date       = $data['Erscheinungsdatum'];
    $duration   = $data['DauerInMinuten'];
    $price      = $data['Preis'];
    $image      = $data['Bild'];
    $visible    = $data['Freigabe'];
    
    $msg_btn  = 'Aktualisieren';
}

$msgErrors = array();

// Wenn das formular einmal abgeschickt wurde.
if (!empty($_POST['button'])) {
    
    // Schauen ob die Pflichtfelder Belegt sind.
    if (empty($company_id)) {
        $msgErrors['fc'] = MSG_FILMFORM_MISSING_COMPANY;
    }
    if (empty($genre_id)) {
        $msgErrors['fg'] = MSG_FILMFORM_MISSING_GENRE;
    }
    if (empty($title)) {
        $msgErrors['ft'] = MSG_FILMFORM_MISSING_TITLE;
    }
    if (empty($date)) {
        $msgErrors['dt'] = MSG_FILMFORM_MISSING_DATE;
    }
}

// Wenn es keine Fehler gab. und die Seite einmal abgeschickt wurde.
if (empty($msgErrors) && !isset($_GET['f'])) {
    
    echo '<pre style="text-align: left;">';
    var_dump($visible);
    echo '</pre>';
    
    $sql = false;
       
    /*
     *  Weiche, wie das Formular verarbeitet werden soll.
     */
    
    // Update (wenn eine FilmID mit übertragen wurde)
    if (!empty($film_id)) {      
        $sql = $sql_update_film ($film_id,
            $genre_id, $company_id, $title, $date, $visible,
            $duration, $image, $desc, $price );
    }
    
    // Speichern (wenn KEINE FilmID mit übertragen wurde)
    else {
        $sql = $sql_insert_newFilm(
            $genre_id, $company_id, $title, $date, $visible,
            $duration, $image, $desc, $price );             
    } 

    /*
     * wenn es eine sql-Anweisung gibt
     * (wenn das formular ohne Fehler abgeschickt wurde.)
     */
    if ($sql !== false) {
        
        echo $sql . "<hr/>";
        
        // Die Daten senden.
        if (mysqli_query($conn, $sql)) {
//            header('Location: ./index.php');
        } else {
            echo 'Der Film wurde NICHT gespeichert!';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="de">
  <head>

    <meta charset="utf-8">
    <title>Filmtitel bearbeiten</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">  

  </head>
  <body>

    <?php
    $isLogedIn = TRUE;
//    include './inc/adminbar.inc.php';
    ?>

    <div class="film-form container">

      <div class="page-header">
        <h1>Filmtitel bearbeiten</h1>
      </div>
        
      <div>

        <p class="fehler"><?php // if ($meldung) echo implode($meldung, "<br>");      ?></p>

        <form method="post" class="well" action="<?php echo $_SERVER["PHP_SELF"]; ?>">

          <!-- alle Filmgesellschaften -->
          <section class="section_select form-group">

            <div class="form-group has-feedback <?php if (isset($msgErrors['fc'])) { echo 'has-error'; } ?>">
              <label class="form_label" for="fc">Filmgesellschaften *</label>
              <p class="msg danger"><?php getError('fc') ?></p>
              <select class="form_input_select form-control" name="fc" id="fc">
                <option class="form_option" value="0">Bitte auswählen</option>
                <?php
                $handler_companies = mysqli_query($conn, $sql_select_companies);
                
                while (($data = mysqli_fetch_assoc($handler_companies)) !== NULL) {
                   $checked = isActive($data['id'], $company_id, 'selected');
                   echo '<option ' . $checked . ' class="form_option" value="' . $data['id'] . '">' . $data['Name'] . '</option>';
                } 
                ?>
              </select>
            </div>

            <!-- alle Genres -->

            <div class="form-group has-feedback <?php if (isset($msgErrors['fg'])) { echo 'has-error'; } ?>">
              <label class="form_label" for="fg">Genres *</label>
              <p class="msg danger"><?php getError('fg'); ?></p>
              <select class="form_input_select form-control" name="fg" id="fg">
                <option class="form_option" value="0">Bitte auswählen</option>
                <?php
                $handler_genres = mysqli_query($conn, $sql_select_genres);
                
                while (($data = mysqli_fetch_assoc($handler_genres)) !== NULL) {
                   $checked = isActive($data['id'], $genre_id, 'selected');
                   echo '<option ' . $checked . ' class="form_option" value="' . $data['id'] . '">' . $data['Name'] . '</option>';
                }
                ?>  
              </select>
            </div>

            <div class="form-group has-feedback <?php if (isset($msgErrors['ft'])) { echo 'has-error'; } ?>">
              <label class="form_label" for="titel">Filmtitel *</label>
              <p class="msg danger"><?php getError('ft'); ?></p>
              <input class="form_input form-control" type="text" name="ft" id="titel" maxlength="150" value="<?php echo $title; ?>">
            </div>

            <div class="form-group">
              <label class="form_label" for="beschreibung">Beschreibung</label>
              <textarea style="resize: vertical" class="form_text form-control" rows="3" name="dc" id="beschreibung"><?php echo $desc; ?></textarea>
            </div>

            <div class="form-group has-feedback <?php if (isset($msgErrors['dt'])) { echo 'has-error'; } ?>">
              <label class="form_label" for="datum">Erscheinungsdatum *</label>
              <p class="msg danger"><?php getError('dt'); ?></p>
              <div class="input-group">
                <div class="input-group-addon"><span class="fa fa-calendar" aria-hidden="true"></span></div>
                <input class="form_input form-control" type="date" name="dt" id="datum" maxlength="10" value="<?php echo $date; ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form_label" for="dauer">Dauer in Minuten</label>
              <div class="input-group">
                <div class="input-group-addon"><span class="fa fa-clock-o" aria-hidden="true"></span></div>
                <input class="form_input form-control" type="number"  min="1" max="9999" step="0.1" name="du" id="dauer" maxlength="3" value="<?php echo $duration; ?>">
                <div class="input-group-addon">min</div>
              </div>
            </div>

            <div class="form-group">
              <label class="form_label" for="preis">Preis</label>
              <div class="input-group">
                <div class="input-group-addon">€</div>
                <input class="form_input form-control" type="number" min="0" max="100" step="0.01" pattern="[0-9]+([\,|\.][0-9]+)?" name="pr" id="preis" maxlength="10" value="<?php echo $price; ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form_label" for="img">Bild</label>
              <div class="input-group">
                <div class="input-group-addon"><span class="fa fa-file-image-o" aria-hidden="true"></span></div>
                <input class="form_input form-control" type="text" name="img" id="bild" maxlength="150" value="<?php echo $image; ?>">
              </div>
            </div>

            <div class="form-group">
              <input class="form_checkbox" type="checkbox" name="vi" id="freigabe" value="1" <?php echo $visible ? 'checked' : '' ?>>
              <label class="form_label_checkbox" for="freigabe">Freigegeben (Für die Kunden sichtbar)</label>
            </div>

            <!-- versteckte Felder für ID -->
            <input type="hidden" name="fid" value="<?php echo !empty($film_id) ? $film_id : $id; ?>">
          </section>


          <section id="section_submit">

            <button class="btn btn-default" type="submit" name="button" value="speichern"><?php echo $msg_btn ?></button>
            <button class="btn btn-default pull-right" type="button" name="button" value="abbrechen" onClick="window.location.href = 'index.php';">Abbrechen</button>

          </section>

        </form>

      </div>

    </div>

  </body>
</html>