import ftplib, sys, os
FTP_HOST="ftpupload.net"; FTP_USER="if0_42492907"; FTP_PASS="ZWCaZdMosYFD"
files=[
    ("leads/add.php",    "/htdocs/leads/add.php"),
    ("leads/edit.php",   "/htdocs/leads/edit.php"),
    ("leads/view.php",   "/htdocs/leads/view.php"),
    ("leads/list.php",   "/htdocs/leads/list.php"),
    ("leads/export.php", "/htdocs/leads/export.php"),
    ("includes/helpers.php", "/htdocs/includes/helpers.php"),
]
try:
    ftp=ftplib.FTP(FTP_HOST,FTP_USER,FTP_PASS,timeout=30)
    for lp,rp in files:
        print(f"  {lp} -> {rp}")
        with open(lp.replace("/",os.sep),"rb") as f: ftp.storbinary(f"STOR {rp}",f)
    ftp.quit(); print("All uploaded!")
except Exception as e: print(f"FTP Error: {e}"); sys.exit(1)
