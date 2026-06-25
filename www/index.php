<?php
include('../config.php');

if (!isset($lieux) || count($lieux) == 0) {
    die("Aucun lieu configuré");
}

$dateFormat = "d/m/Y";

$mergePdfCmd = "pdftk INPUT cat output OUTPUT 2>&1";
$typstCmd = "typst compile --root . INPUT OUTPUT 2>&1";

$outputFolder = "output";

function lastFiles($glob) {
    $files = glob($glob);
    $files = array_combine($files, array_map("filemtime", $files));
    arsort($files);
    return $files;
}

function isAdmin() {
    global $admins;
    return in_array($_SERVER['REMOTE_USER'], $admins);
}

// Les champs de formulaire à placer dans le template
$formFields = [
    "name" => [ "type" => "text", "text" => "Nom" ],
    "birthdate" => [ "type" => "date", "text" => "Date de naissance" ],
    "birthplace" => [ "type" => "text", "text" => "Lieu de naissance" ],
    "arrivaldate" => [ "type" => "date", "text" => "Date d'hébergement" ],
    "docdate" => [ "type" => "date", "text" => "Date de l'attestation", "default" => date("Y-m-d") ],
];

// Lorsque le formulaire a été soumis, les champs vides ou invalides
$missingFields = [];
foreach($formFields as $field => $props) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

// Est-ce qu'un formulaire a été soumis ?
$freshForm = empty($_POST);
if (!$freshForm) {
    if (!isset($_POST["lieu"])) {
        die("PAS DE LIEU");
    }

    if (!array_key_exists($_POST["lieu"], $lieux)) {
        die("MAUVAIS LIEU!");
    }

    $typstTemplate = "templates/" . $_POST["lieu"] . "/main.typ";
    $extraGlob = "templates/" . $_POST["lieu"] . "/extra/*.pdf";
}


// Le formulaire est-il complet?
if (empty($missingFields)) {
    $hash = md5(serialize($_POST));

    $jsonname = "$outputFolder/$hash.json";
    if (!file_exists($jsonname)) {
        $form = $_POST;
        $form["submitter"] = $_SERVER["REMOTE_USER"];
        $form["lieu"] = $_POST["lieu"];
        file_put_contents($jsonname, json_encode($form));
    }

    $typname = "$outputFolder/$hash.typ";
    if (!file_exists($typname)) {
        $template = file_get_contents($typstTemplate);
        foreach($formFields as $key => $props) {
            $val = ($props["type"] == "date") ? date($dateFormat, strtotime($_POST[$key])) : $_POST[$key];
            $template = str_replace("{{{$key}}}", $val, $template);
        }
        file_put_contents($typname, $template);
    }

    $pdfname = "$outputFolder/$hash.pdf";
    if (!file_exists($pdfname)) {
        $output = null;
        $retval = null;

        $customCmd = strtr($typstCmd, [ "INPUT" => $typname, "OUTPUT" => $pdfname ]);
        setlocale(LC_ALL, 'en_US.UTF-8');
        putenv('LC_ALL='.'en_US.UTF-8');
        exec($customCmd, $output, $retval);

        if ($retval != 0) {
            echo "ERROR ($retval):\n<br>";
            echo implode("\n<br>", $output);
            die();
        }
    }

    $combinedpdfname = "$outputFolder/$hash.combined.pdf";
    if (!file_exists($combinedpdfname)) {
        $output = null;
        $retval = null;

        $pages = [ $pdfname ];
        $extraPages = glob($extraGlob);
        $pages = array_merge($pages, $extraPages);
        $customCmd = strtr($mergePdfCmd, [ "INPUT" => implode(" ", $pages), "OUTPUT" => $combinedpdfname ]);
        // Workaround to support UTF-8 templates/files
        setlocale(LC_ALL, 'en_US.UTF-8');
        putenv('LC_ALL='.'en_US.UTF-8');
        exec($customCmd, $output, $retval);

        if ($retval != 0) {
            echo "ERROR when merging pages ($retval):\n<br>";
            echo implode("\n<br>", $output);
            die();
        }
    }

    // Redirect to the generated PDF
    header("Location: $combinedpdfname");
} else {
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Attestation d'hébergement</title>
    <link rel="stylesheet" href="bulma.min.css">
  </head>
  <body class="block container is-max-desktop mt-4 mb-4">
    <h1 class="title is-1 has-text-centered">Attestation d'hébergement</h1>
    <form method="POST" accept-charset="UTF-8">
<?php
    foreach($formFields as $field => $props) {
        $type = $props["type"];
        $text = $props["text"];
        $default = array_key_exists("default", $props) ? $props["default"] : "";
        $current = $freshForm ? $default : (in_array($field, $missingFields) ? "" : $_POST[$field]);
        $extraClass = (($freshForm == false) && in_array($field, $missingFields)) ? "is-danger" : "";

        echo "      <div class='field'>\n";
        echo "        <br><label class='label' for='$field'>$text :</label>\n";
        echo "        <div class='control'>\n";
        echo "          <input class='input $extraClass' id='$field' name='$field' type='$type' value='$current'>\n";
        echo "        </div>\n";
        echo "      </div>";
    }
?>
      <div class="field">
        <br><label class="label" for="lieu">Adresse</label>
        <div class="control">
        <select class="input" id="lieu" name="lieu" value="<?php echo array_keys($lieux)[0] ?>">
<?php foreach ($lieux as $key => $value) { ?>
<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
<?php } ?>
          </select>
        </div>
      </div>
      <div class="field mt-5">
        <div class="control has-text-centered">
            <input class="button is-link is-centered is-center" type="submit" value="Confirmer">
        </div>
      </div>
    </form>
<?php
    // Si c'est unE admin, on affiche les dernières", attestations pour pouvoir les imprimer
    if (isAdmin()) {
?>
    <h1 class="title is-1 has-text-centered mt-4">Dernières attestations</h1>
    <ul>
<?php
        foreach (lastFiles("output/*.json") as $savedJson => $mtime) {
            $savedPdf = str_replace(".json", ".combined.pdf", $savedJson);
            if (!file_exists($savedPdf)) {
                continue;
            }
            $savedForm = file_get_contents($savedJson);
            $savedForm = json_decode($savedForm, true);
            $savedName = htmlspecialchars($savedForm["name"], ENT_QUOTES);
            echo "<li><a href='$savedPdf'>$savedName</a></li>";
        }
?>    
    </ul>
<?php
    }
?>",
  </body>
</html>
<?php
}
// FOO
?>
