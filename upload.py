import ftplib
import os
import sys

FTP_HOST = "ftpupload.net"
FTP_USER = "if0_42492907"
FTP_PASS = "ZWCaZdMosYFD"
REMOTE_DIR = "/htdocs"

def upload_dir(ftp, local_path, remote_path):
    print(f"Uploading directory {local_path} to {remote_path}")
    try:
        ftp.mkd(remote_path)
    except ftplib.error_perm:
        pass

    for item in os.listdir(local_path):
        if item in ['.git', '.gitignore', 'README.md', 'database.sql', 'migration.sql', 'migration_v3.sql', 'migration_v4.sql', 'migration_v5.sql', 'infinityfree_deployment.md', 'fix_pwd.php', 'add_leads.php', 'upload.py']:
            continue
            
        local_item = os.path.join(local_path, item)
        remote_item = f"{remote_path}/{item}"
        
        if os.path.isfile(local_item):
            print(f"Uploading file {local_item} to {remote_item}")
            with open(local_item, 'rb') as f:
                try:
                    ftp.storbinary(f'STOR {remote_item}', f)
                except Exception as e:
                    print(f"Failed to upload {local_item}: {e}")
        elif os.path.isdir(local_item):
            upload_dir(ftp, local_item, remote_item)

if __name__ == "__main__":
    try:
        print("Connecting to FTP...")
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        print("Connected successfully.")
        
        try:
            ftp.cwd(REMOTE_DIR)
        except:
            pass
            
        upload_dir(ftp, ".", REMOTE_DIR)
        
        ftp.quit()
        print("Upload completed successfully!")
    except Exception as e:
        print(f"FTP Error: {e}")
        sys.exit(1)