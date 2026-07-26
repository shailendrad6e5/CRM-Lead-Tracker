import ftplib
import sys
import os

FTP_HOST = "ftpupload.net"
FTP_USER = "if0_42492907"
FTP_PASS = "ZWCaZdMosYFD"

files_to_upload = [
    ("includes/auth.php",    "/htdocs/includes/auth.php"),
    ("includes/csrf.php",    "/htdocs/includes/csrf.php"),
    ("includes/helpers.php", "/htdocs/includes/helpers.php"),
    ("includes/footer.php",  "/htdocs/includes/footer.php"),
    ("login.php",            "/htdocs/login.php"),
    ("register.php",         "/htdocs/register.php"),
    ("profile.php",          "/htdocs/profile.php"),
    ("leads/add.php",        "/htdocs/leads/add.php"),
    ("leads/edit.php",       "/htdocs/leads/edit.php"),
    ("leads/list.php",       "/htdocs/leads/list.php"),
    ("leads/view.php",       "/htdocs/leads/view.php"),
    ("migration.sql",        "/htdocs/migration.sql"),
]

try:
    print("Connecting to FTP...")
    ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS, timeout=30)
    for local_path, remote_path in files_to_upload:
        lp = local_path.replace("/", os.sep)
        print(f"Uploading {lp} -> {remote_path}...")
        with open(lp, "rb") as f:
            ftp.storbinary(f"STOR {remote_path}", f)
    ftp.quit()
    print("All files uploaded successfully!")
except Exception as e:
    print(f"FTP Error: {e}")
    sys.exit(1)
