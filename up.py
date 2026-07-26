import ftplib, sys, os
FTP_HOST="ftpupload.net"; FTP_USER="if0_42492907"; FTP_PASS="ZWCaZdMosYFD"
files=[
    ("leads/list.php",        "/htdocs/leads/list.php"),
    ("assets/js/script.js",   "/htdocs/assets/js/script.js"),
]
try:
    ftp=ftplib.FTP(FTP_HOST,FTP_USER,FTP_PASS,timeout=30)
    for lp,rp in files:
        print(f"  {lp}")
        with open(lp.replace("/",os.sep),"rb") as f: ftp.storbinary(f"STOR {rp}",f)
    ftp.quit(); print("Done!")
except Exception as e: print(f"FTP Error: {e}"); sys.exit(1)
