// Vygeneruje a vypíše náhodné číslo mezi 1 a 100
echo "Náhodné číslo: " . rand(1, 100);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Moderní web s Bootstrapem</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<?php
	// include subheader (kept as simple PHP include so it can be reused across pages)
	$subheader = __DIR__ . '/subheader.php';
	if (file_exists($subheader)) {
		include_once $subheader;
	}
	?>
	<div class="container py-5">
		<div class="row justify-content-center">
			<div class="col-md-6">
				<div class="card shadow-sm">
					<div class="card-body">
						<h1 class="mb-4 text-center">Náhodné číslo</h1>
						<div class="alert alert-primary text-center" role="alert">
							<?php echo rand(1, 100); ?>
						</div>
						<div class="d-grid gap-2">
							<a href="" class="btn btn-success">Vygenerovat znovu</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>