#!/usr/bin/env python3
"""
PDF Steganography Tool (Standard Python 3 Library)
--------------------------------------------------
Alat untuk membuat file PDF dan menyisipkan/mengekstrak data/pesan rahasia
menggunakan teknik Steganografi PDF.

Metode Steganografi yang didukung:
1. EOF Append (EOF)      : Menyisipkan payload setelah marker %%EOF (Paling aman & fleksibel).
2. Metadata Object (META): Menyisipkan payload terenkripsi ke dalam objek metadata PDF.
3. Stream Hidden (STREAM): Menyisipkan teks rahasia ke dalam stream objek PDF (Invisible text layer).
"""

import sys
import os
import argparse
import base64
import hashlib
import json
import re

MAGIC_HEADER = "---BEGIN_PDF_STEGO_PAYLOAD---"
MAGIC_FOOTER = "---END_PDF_STEGO_PAYLOAD---"

def encrypt_decrypt(data: bytes, key: str) -> bytes:
    """Enkripsi/Dekripsi sederhana menggunakan XOR stream cipher dengan key berbasis SHA-256."""
    if not key:
        return data
    key_bytes = hashlib.sha256(key.encode('utf-8')).digest()
    result = bytearray()
    for i, byte in enumerate(data):
        key_byte = key_bytes[i % len(key_bytes)]
        result.append(byte ^ key_byte)
    return bytes(result)

def generate_base_pdf(filename: str, title: str = "Dokumen Publik", content_text: str = "Ini adalah dokumen PDF standar yang terlihat normal."):
    """Membuat file PDF standar yang valid menggunakan sintaks murni PDF 1.4."""
    pdf_template = f"""%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 120 >>
stream
BT
/F1 18 Tf
50 720 Td
({title}) Tj
/F1 12 Tf
0 -40 Td
({content_text}) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000244 00000 n 
0000000415 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
489
%%EOF
"""
    with open(filename, 'wb') as f:
        f.write(pdf_template.encode('latin1'))
    print(f"[+] PDF Dasar dibuat: '{filename}'")

def embed_payload(input_pdf: str, output_pdf: str, secret_message: str, key: str = "", method: str = "eof"):
    """Menyisipkan pesan rahasia ke dalam file PDF."""
    if not os.path.exists(input_pdf):
        raise FileNotFoundError(f"File PDF '{input_pdf}' tidak ditemukan.")

    # Enkripsi & Encoding Payload
    secret_bytes = secret_message.encode('utf-8')
    if key:
        encrypted = encrypt_decrypt(secret_bytes, key)
        payload_data = base64.b64encode(encrypted).decode('utf-8')
        is_encrypted = True
    else:
        payload_data = base64.b64encode(secret_bytes).decode('utf-8')
        is_encrypted = False

    payload_dict = {
        "encrypted": is_encrypted,
        "method": method,
        "data": payload_data
    }
    payload_json = json.dumps(payload_dict)
    formatted_payload = f"\n{MAGIC_HEADER}\n{payload_json}\n{MAGIC_FOOTER}\n"

    with open(input_pdf, 'rb') as f:
        pdf_data = f.read()

    if method == "eof":
        # Menyisipkan di akhir file PDF setelah %%EOF
        new_pdf_data = pdf_data + formatted_payload.encode('utf-8')
    elif method == "metadata":
        # Menyisipkan ke dalam kamus metadata Info object
        eof_pos = pdf_data.rfind(b"%%EOF")
        if eof_pos != -1:
            meta_obj = f"\n6 0 obj\n<< /StegoData ({payload_json.replace('(', '\\(').replace(')', '\\)')}) >>\nendobj\n"
            new_pdf_data = pdf_data[:eof_pos] + meta_obj.encode('utf-8') + pdf_data[eof_pos:]
        else:
            new_pdf_data = pdf_data + formatted_payload.encode('utf-8')
    else: # stream
        eof_pos = pdf_data.rfind(b"%%EOF")
        if eof_pos != -1:
            hidden_stream = f"\n7 0 obj\n<< /Length {len(formatted_payload)} >>\nstream\n{formatted_payload}\nendstream\nendobj\n"
            new_pdf_data = pdf_data[:eof_pos] + hidden_stream.encode('utf-8') + pdf_data[eof_pos:]
        else:
            new_pdf_data = pdf_data + formatted_payload.encode('utf-8')

    with open(output_pdf, 'wb') as f:
        f.write(new_pdf_data)

    print(f"[✓] Berhasil menyisipkan pesan rahasia ke '{output_pdf}' menggunakan metode '{method}'.")

def extract_payload(pdf_file: str, key: str = "") -> str:
    """Mengekstrak pesan rahasia dari file PDF."""
    if not os.path.exists(pdf_file):
        raise FileNotFoundError(f"File PDF '{pdf_file}' tidak ditemukan.")

    with open(pdf_file, 'rb') as f:
        content = f.read().decode('latin1', errors='ignore')

    # Cari stego payload menggunakan regex
    pattern = re.escape(MAGIC_HEADER) + r"\s*(.*?)\s*" + re.escape(MAGIC_FOOTER)
    match = re.search(pattern, content, re.DOTALL)

    if not match:
        # Coba cari di Metadata /StegoData
        meta_match = re.search(r"/StegoData\s*\((.*?)\)", content, re.DOTALL)
        if meta_match:
            raw_payload = meta_match.group(1).replace('\\(', '(').replace('\\)', ')')
            payload_dict = json.loads(raw_payload)
        else:
            print("[!] Tidak ditemukan payload steganografi pada file PDF ini.")
            return ""
    else:
        raw_json = match.group(1)
        payload_dict = json.loads(raw_json)

    is_encrypted = payload_dict.get("encrypted", False)
    payload_data = payload_dict.get("data", "")
    encrypted_bytes = base64.b64decode(payload_data)

    if is_encrypted:
        if not key:
            print("[!] Pesan terenkripsi! Membutuhkan kata sandi (--key) untuk dekripsi.")
            return ""
        decrypted_bytes = encrypt_decrypt(encrypted_bytes, key)
    else:
        decrypted_bytes = encrypted_bytes

    try:
        secret_message = decrypted_bytes.decode('utf-8')
        print(f"[✓] Pesan Rahasia Ditemukan:")
        print("----------------------------------------")
        print(secret_message)
        print("----------------------------------------")
        return secret_message
    except Exception as e:
        print(f"[!] Gagal mendeskripsi pesan. Kemungkinan kata sandi salah! Error: {e}")
        return ""

def main():
    parser = argparse.ArgumentParser(description="PDF Steganography Tool - Buat & Sisipkan Pesan Rahasia ke PDF")
    parser.add_argument("-g", "--generate", help="Buat PDF contoh baru", action="store_true")
    parser.add_argument("-i", "--input", help="File PDF sumber/input", default="sample.pdf")
    parser.add_argument("-o", "--output", help="File PDF output dengan steganografi", default="stego_output.pdf")
    parser.add_argument("-e", "--embed", help="Pesan rahasia yang ingin disisipkan")
    parser.add_argument("-x", "--extract", help="Ekstrak pesan rahasia dari PDF target", action="store_true")
    parser.add_argument("-k", "--key", help="Kata sandi/Kunci enkripsi rahasia", default="")
    parser.add_argument("-m", "--method", help="Metode steganografi (eof, metadata, stream)", default="eof", choices=["eof", "metadata", "stream"])

    args = parser.parse_args()

    if args.generate:
        generate_base_pdf(args.output)
        if args.embed:
            embed_payload(args.output, args.output, args.embed, args.key, args.method)
    elif args.embed:
        if not os.path.exists(args.input):
            # Jika input belum ada, buat otomatis
            generate_base_pdf(args.input)
        embed_payload(args.input, args.output, args.embed, args.key, args.method)
    elif args.extract:
        extract_payload(args.input, args.key)
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
