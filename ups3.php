<?php
require 'vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Instantiate an Amazon S3 client
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-south-1',
    'credentials' => [
        'key'    => '',   // your AWS access key
        'secret' => '' // your AWS secret key
    ]
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["anyfile"]) && $_FILES["anyfile"]["error"] == 0) {
        
        $allowed = [
            "jpg"  => "image/jpg",
            "jpeg" => "image/jpeg",
            "gif"  => "image/gif",
            "png"  => "image/png"
        ];

        $filename = $_FILES["anyfile"]["name"];
        $filetype = $_FILES["anyfile"]["type"];
        $filesize = $_FILES["anyfile"]["size"];

        // Validate file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            die("Error: Please select a valid file format.");
        }

        // Validate file size (10 MB max)
        $maxsize = 10 * 1024 * 1024;
        if ($filesize > $maxsize) {
            die("Error: File size is larger than the allowed limit.");
        }

        // Validate type of file
        if (in_array($filetype, $allowed)) {
            
            // Ensure uploads directory exists
            if (!is_dir("uploads")) {
                mkdir("uploads", 0777, true);
            }

            $uploadPath = "uploads/" . $filename;

            if (file_exists($uploadPath)) {
                echo $filename . " already exists.";
            } else {
                if (move_uploaded_file($_FILES["anyfile"]["tmp_name"], $uploadPath)) {
                    
                    $bucket = 'acleanblewali';   // your bucket name
                    $file_Path = __DIR__ . '/uploads/' . $filename;
                    $key = basename($file_Path);

                    try {
                        // Upload file to S3
                        $result = $s3Client->putObject([
                            'Bucket' => $bucket,
                            'Key'    => $key,
                            'Body'   => fopen($file_Path, 'r'),
                            'ACL'    => 'public-read', // file will be public
                        ]);

                        $urls3 = $result->get('ObjectURL');
                        echo "✅ Image uploaded successfully. S3 URL: " . $urls3 . "<br>";

                        // Replace with CloudFront domain
                        $cfurl = str_replace(
                            "https://{$bucket}.s3.ap-south-1.amazonaws.com",
                            "https://d2aq95sjge1bd9.cloudfront.net",
                            $urls3
                        );
                        echo "🌍 CloudFront URL: " . $cfurl . "<br>";

                        // Save details in MySQL
                        $name    = $_POST["name"];
                        $caption = $_POST["caption"];

                        $servername = "database-1.cz0ea2iyuu1a.ap-south-1.rds.amazonaws.com";
                        $username   = "root";
                        $password   = "Pass1234";
                        $dbname     = "facebook";

                        $conn = mysqli_connect($servername, $username, $password, $dbname);

                        if (!$conn) {
                            die("Connection failed: " . mysqli_connect_error());
                        }

                        $sql = "INSERT INTO posts(name, caption, url, cfurl) 
                                VALUES('$name', '$caption', '$urls3', '$cfurl')";

                        if (mysqli_query($conn, $sql)) {
                            echo "✅ Record saved in database.";
                        } else {
                            echo "❌ Database Error: " . mysqli_error($conn);
                        }

                        mysqli_close($conn);

                    } catch (AwsException $e) {
                        echo "❌ Error uploading to S3: " . $e->getMessage();
                    }

                } else {
                    echo "❌ File move failed.";
                }
            }
        } else {
            echo "❌ Invalid file type.";
        }
    } else {
        echo "❌ Error: " . $_FILES["anyfile"]["error"];
    }
}
?>