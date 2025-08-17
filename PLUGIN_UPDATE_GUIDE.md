# Plugin Update Guide

## Problem: Plugin installerer ny version i stedet for at overskrive

Hvis du oplever at WordPress opretter en ny mappe i stedet for at overskrive den eksisterende plugin, følg disse trin:

### 1. Deaktiver og slet den gamle version

1. Gå til **WordPress Admin → Plugins**
2. **Deaktiver** "AI Layout for YOOtheme" hvis det er aktiveret
3. **Slet** den gamle version helt
4. **Slet** eventuelle ekstra mapper med lignende navne

### 2. Ryd op i wp-content/plugins/

```bash
# Tjek om der er flere mapper
ls -la wp-content/plugins/ | grep -i "ai-layout"

# Slet alle gamle versioner
rm -rf wp-content/plugins/ai-layout-for-yootheme*
```

### 3. Upload den nye version

1. **Download** den nyeste version fra GitHub Releases
2. **Upload** ZIP filen via WordPress Admin → Plugins → Add New → Upload Plugin
3. **Aktiver** pluginet

### 4. Hvis problemet fortsætter

**Tjek plugin header information:**
- Plugin Name skal være præcis det samme
- Version nummer skal være korrekt
- Ingen ekstra mellemrum eller tegn

**Manuel installation:**
1. **Download** ZIP filen
2. **Udpak** til en mappe
3. **Upload** mappen til `wp-content/plugins/` via FTP
4. **Aktiver** via WordPress Admin

### 5. Verificer installation

**Efter installation skal du se:**
- ✅ Kun én mappe: `wp-content/plugins/ai-layout-for-yootheme/`
- ✅ Plugin vises korrekt i plugins liste
- ✅ Ingen duplikater eller fejl

### 6. Fejlfinding

**Hvis der stadig er problemer:**
1. **Tjek error log**: `wp-content/debug.log`
2. **Verificer filrettigheder**: 755 for mapper, 644 for filer
3. **Tjek WordPress version**: Kræver 6.0+
4. **Tjek PHP version**: Kræver 7.4+

## Support

Hvis du stadig oplever problemer, opret et issue på GitHub:
https://github.com/samuelsamuraj/AI-Layouts-for-Yootheme-Pro/issues
