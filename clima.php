<?php
require_once '../load_env.php';
session_start();
$apiKey = $_ENV["SECRET"];

$ciudad = urlencode($_GET['ciudad']);
$url = "https://api.openweathermap.org/data/2.5/weather?q=" . $ciudad . "&units=metric&lang=es&appid=" . $apiKey;

$opts = [
    "http" => [
        "method" => "GET",
        "ignore_errors" => true,
    ]
];

$context = stream_context_create($opts);

$response = @file_get_contents($url, false, $context);

$data = json_decode($response, true);

if ($data["cod"] == "404") {
    $_SESSION["error_msg"] = "Ciudad no encontrada.";
    header("Location: ./error.php");
    exit();
} else if ($data["cod"] == "400") {
    $_SESSION["error_msg"] = "Porfavor ingrese una ciudad.";
    header("Location: ./error.php");
    exit();
} else if ($data["cod"] != "200") {
    $_SESSION["error_msg"] = "Ha ocurrido un error. Inténtelo de nuevo más tarde.";
    header("Location: ./error.php");
    exit();
}

$timezoneString = file_get_contents('./timezones.json');
$timezoneData = json_decode($timezoneString, true);
$countryCode = $data['sys']['country'];
$timezone = $timezoneData[$countryCode][0];


function countryCodeToEmoji(string $code): string
{
    $code = strtoupper($code);
    $emoji = '';

    for ($i = 0; $i < 2; $i++) {
        $emoji .= mb_convert_encoding(
            '&#' . (127397 + ord($code[$i])) . ';',
            'UTF-8',
            'HTML-ENTITIES'
        );
    }

    return $emoji;
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <title>Clima</title>
</head>

<body class="text-center">
    <header class="m-3">
        <nav class="navbar navbar-expand-lg bg-body-tertiary rounded">
            <div class="container-fluid">
                <a href="./index.html" class="navbar-brand">OpenClima</a>
            </div>
        </nav>
    </header>
    <section class="m-3">
        <form action="clima.php" class="mb-3">
            <div class="input-group">
                <span class="input-group-text">Ciudad</span>
                <input type="text" name="ciudad" id="ciudad" class="form-control" placeholder="Temuco" value="<?php echo $ciudad; ?>" required>
                <button class="btn btn-primary">Buscar</button>
            </div>
        </form>
        <div class="card">
            <div class="row g-0">
                <div class="col-md-4 border-end">
                    <div class="card-body mx-auto">
                        <h5 class="card-title align-center"><?php echo countryCodeToEmoji($data["sys"]["country"]) . " " . $data["name"]; ?></h5>
                        <img src=<?php echo "./assets/imgs/clima_iconos/" . $data["weather"]["0"]["icon"] . ".svg" ?> alt="Clima" width="200px">
                        <span><?php
                                $dt = new DateTime("now", new DateTimeZone($timezone));
                                echo $dt->format("H:i:s");
                                ?></span>
                        <p><?php echo round($data["main"]["temp"]) . "° " . ucwords($data["weather"]["0"]["description"]); ?></p>
                    </div>
                </div>
                <div class="col-md-4 m-auto px-5">
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/sunrise.svg" alt="">
                        </span>
                        Amanecer: <?php
                                    $timestampSunrise = $data["sys"]["sunrise"];
                                    $dtSunrise = new DateTime("@$timestampSunrise");
                                    $dtSunrise->setTimezone(new DateTimeZone($timezone));
                                    echo $dtSunrise->format('H:i:s');
                                    ?>
                    </div>
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/sunset.svg" alt="">
                        </span>
                        Atardecer: <?php
                                    $timestampSunet = $data["sys"]["sunset"];
                                    $dtSunset = new DateTime("@$timestampSunet");
                                    $dtSunset->setTimezone(new DateTimeZone($timezone));
                                    echo $dtSunset->format('H:i:s');
                                    ?>
                    </div>
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/clouds.svg" alt="">
                        </span>
                        Nubosidad: <?php echo $data["clouds"]["all"] . "%" ?>
                    </div>
                </div>
                <div class="col-md-4 m-auto">
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/humidity.svg" alt="">
                        </span>
                        Humedad: <?php echo $data["main"]["humidity"] . "%" ?>
                    </div>
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/wind.svg" alt="">
                        </span>
                        Viento: <?php echo round($data["wind"]["speed"] * 3.6, 2) . " km/h" ?>
                    </div>
                    <div class="fs-5 d-flex align-items-center">
                        <span class="badge">
                            <img src="./assets/imgs/detalles_iconos/gusts.svg" alt="">
                        </span>
                        Ráfagas de viento: <?php echo round($data["wind"]["gust"] * 3.6, 2) . " km/h" ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="bd-footer border-top fixed-bottom p-2">
        <span>&copy; Oscar Cariaga 2025</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>

</html>