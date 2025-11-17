# Sistem de Gestiune Service Auto

Acest proiect este o aplicație web statică pentru administrarea unui service auto pe bază de abonament.

## Caracteristici Implementate

### 1. Management Interactiv al Clienților
- **Pagina de Gestiune Clienți (`admin_clients.html`):** Permite adăugarea și ștergerea clienților printr-o interfață dinamică.
- **Ferestre Modale:** Acțiunile de adăugare și confirmare a ștergerii se realizează prin ferestre modale (pop-ups) pentru a evita reîncărcarea paginii. Datele sunt gestionate momentan printr-un array JavaScript simulat.

### 2. Banner de Consimțământ Cookie
- **Notificare:** Un banner discret apare în partea de jos a paginii pentru a informa utilizatorii despre utilizarea cookie-urilor.
- **Interacțiune:** Banner-ul poate fi închis de utilizator, iar preferința sa este salvată în `localStorage` pentru a nu mai fi afișat la vizitele viitoare.

### 3. Animații și Efecte Vizuale Moderne
Pentru a îmbunătăți experiența utilizatorului, au fost adăugate următoarele animații:
- **Efecte Hover pe Butoane:** Butoanele se înalță subtil la trecerea mouse-ului peste ele.
- **Fundal Animat în Secțiunea Hero:** Pagina principală are un fundal cu gradient care își schimbă culorile lent.
- **Efect de Reflector (Spotlight):** Un efect de lumină urmărește cursorul mouse-ului în secțiunea hero, adăugând un element interactiv.
- **Animații la Derulare (Scroll):** Elementele de pe pagină (carduri, widget-uri) apar cu un efect de "fade-in" pe măsură ce utilizatorul derulează pagina.

### 4. Îmbunătățiri de UI/UX
- **Buton "Back to Top":** pictograma emoji a fost înlocuită cu o săgeată neagră (`↑`) pe toate paginile pentru un aspect mai profesional.
- **Structură HTML Semantică:** S-au adus îmbunătățiri structurii HTML pentru o mai bună accesibilitate și SEO.
