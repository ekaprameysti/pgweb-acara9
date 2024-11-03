<?php   
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Koneksi ke database
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "pgweb-acara8";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
} 

// Proses hapus data jika parameter id ada di URL
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM tabel_penduduk WHERE id = $delete_id";
    if ($conn->query($delete_sql) === TRUE) {
        echo "Data berhasil dihapus.";
    } else {
        echo "Error menghapus data: " . $conn->error;
    }
}

// Proses edit data jika parameter edit_id ada di POST
if (isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $kecamatan = $_POST['Kecamatan'];
    $longitude = $_POST['Longitude'];
    $latitude = $_POST['Latitude'];
    $luas = $_POST['Luas'];
    $jumlah_penduduk = $_POST['Jumlah_Penduduk'];

    $edit_sql = "UPDATE tabel_penduduk SET Kecamatan='$kecamatan', Longitude='$longitude', Latitude='$latitude', Luas='$luas', Jumlah_Penduduk='$jumlah_penduduk' WHERE id=$edit_id";
    if ($conn->query($edit_sql) === TRUE) {
        echo "Data berhasil diupdate.";
    } else {
        echo "Error mengupdate data: " . $conn->error;
    }
}

// Menampilkan data
$sql = "SELECT * FROM tabel_penduduk"; 
$result = $conn->query($sql);
$markers = []; 

$tableHtml = "<table class='data-table'><tr> 
<th>Kecamatan</th> 
<th>Longitude</th> 
<th>Latitude</th> 
<th>Luas</th> 
<th>Jumlah Penduduk</th>
<th>Aksi</th>
</tr>"; 

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . $row["Kecamatan"] . "</td>";
        $tableHtml .= "<td>" . $row["Longitude"] . "</td>";
        $tableHtml .= "<td>" . $row["Latitude"] . "</td>";
        $tableHtml .= "<td>" . $row["Luas"] . "</td>";
        $tableHtml .= "<td>" . $row["Jumlah_Penduduk"] . "</td>";
        $tableHtml .= "<td>
            <a href='?edit_id=" . $row['id'] . "'>Edit</a> |
            <a href='?delete_id=" . $row['id'] . "' onclick=\"return confirm('Apakah Anda yakin ingin menghapus data ini?')\">Hapus</a>
        </td>";
        $tableHtml .= "</tr>";

        // Simpan data marker
        $markers[] = [
            'Latitude' => $row["Latitude"],
            'Longitude' => $row["Longitude"],
            'Nama' => $row["Kecamatan"]
        ];
    }
} else {
    $tableHtml .= "<tr><td colspan='6'>Tidak ada data</td></tr>";
}
$tableHtml .= '</table>';

// Menutup koneksi
$conn->close(); 
?> 

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Peta Jumlah Penduduk di Kabupaten Sleman</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        /* CSS styles */
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }
        h1 {
            margin-top: 20px;
            font-size: 24px;
            color: #333;
        }
        #map {
            width: 100%;
            height: 100vh; /* Memastikan peta mengisi seluruh tinggi layar */
            margin-top: 20px;
            flex-grow: 1; /* Membuat peta tumbuh memenuhi ruang yang tersedia */
        }
        .table-container {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
        }
        .data-table-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-collapse: collapse;
            border: 1px solid #ddd;
            width: auto;
            max-width: 600px;
            margin-right: 20px; /* Memberi jarak antara tabel dan form */
            z-index: 10; /* Menempatkan tabel di atas peta */
            position: relative; /* Membuat konteks untuk z-index */
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            white-space: nowrap;
        }
        .data-table th {
            background-color: rgba(0, 123, 255, 0.8);
            color: white;
        }
        .edit-form-container {
            background-color: #ffffff; 
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .toggle-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h1>WEB GIS KABUPATEN SLEMAN</h1>

    <!-- Tombol untuk menampilkan atau menyembunyikan tabel -->
    <button class="toggle-button" onclick="toggleTable()">Show/Hide Data</button>

    <div class="table-container" id="tableContainer">
        <div class="data-table-container" id="dataTableContainer">
            <?php echo $tableHtml; ?>
        </div>

        <!-- Form Edit Data -->
        <div class="edit-form-container" id="editFormContainer">
            <h3>Edit Data</h3>
            <?php if (isset($_GET['edit_id'])): 
                $edit_id = $_GET['edit_id'];
                $conn = new mysqli($servername, $username, $password, $dbname);
                $edit_sql = "SELECT * FROM tabel_penduduk WHERE id = $edit_id";
                $edit_result = $conn->query($edit_sql);
                $edit_data = $edit_result->fetch_assoc();
            ?>
            <form method="post" action="index.php">
                <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <label>Kecamatan: <input type="text" name="Kecamatan" value="<?php echo $edit_data['Kecamatan']; ?>"></label><br>
                <label>Longitude: <input type="text" name="Longitude" value="<?php echo $edit_data['Longitude']; ?>"></label><br>
                <label>Latitude: <input type="text" name="Latitude" value="<?php echo $edit_data['Latitude']; ?>"></label><br>
                <label>Luas: <input type="text" name="Luas" value="<?php echo $edit_data['Luas']; ?>"></label><br>
                <label>Jumlah Penduduk: <input type="text" name="Jumlah_Penduduk" value="<?php echo $edit_data['Jumlah_Penduduk']; ?>"></label><br>
                <input type="submit" value="Update Data">
            </form>
            <?php $conn->close(); endif; ?>
        </div>
    </div>

    <!-- Peta Leaflet -->
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Inisialisasi peta
        var map = L.map("map").setView([-7.7691983, 110.4033279], 14);

        // Tile Layer Base Map
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
        }).addTo(map);

        // Menambahkan marker ke peta
        var markers = <?php echo json_encode($markers); ?>;
        markers.forEach(function(marker) {
            L.marker([marker.Latitude, marker.Longitude]).addTo(map).bindPopup(marker.Nama);
        });

        // Fungsi untuk toggle visibilitas tabel data dan form
        function toggleTable() {
            var tableContainer = document.getElementById("dataTableContainer");
            var editFormContainer = document.getElementById("editFormContainer");
            tableContainer.style.display = tableContainer.style.display === "none" ? "block" : "none";
            editFormContainer.style.display = editFormContainer.style.display === "none" ? "block" : "none";
        }
    </script>
</body>
</html>
