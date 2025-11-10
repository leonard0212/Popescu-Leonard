
document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTE DIN DOM ---
    const clientsTableBody = document.getElementById('clients-table-body');
    const searchInput = document.getElementById('client-search-input');
    const addClientBtn = document.getElementById('add-client-btn');
    const addClientModal = document.getElementById('add-client-modal');
    const addClientForm = document.getElementById('add-client-form');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

    // --- DATE ȘI STARE ---
    let mockClients = [
        { id: 1, name: 'Popescu Ion', phone: '0722 123 456', email: 'ion.popescu@email.com', equipmentCount: 2, registerDate: '2023-01-15' },
        { id: 2, name: 'Vasilescu Ana', phone: '0744 987 654', email: 'ana.v@email.com', equipmentCount: 1, registerDate: '2022-11-20' },
        { id: 3, name: 'Georgescu Dan', phone: '0766 555 888', email: 'dan.georgescu@email.com', equipmentCount: 3, registerDate: '2023-03-10' },
        { id: 4, name: 'Ionescu Maria', phone: '0733 111 222', email: 'maria.ionescu@email.com', equipmentCount: 1, registerDate: '2021-07-01' },
        { id: 5, name: 'Radu Tudor', phone: '0755 333 444', email: 'tudor.radu@email.com', equipmentCount: 5, registerDate: '2023-02-25' },
    ];
    let clientIdToDelete = null;

    /**
     * Afișează clienții în tabel
     * @param {Array} clients - Lista de clienți de afișat
     */
    const displayClients = (clients) => {
        clientsTableBody.innerHTML = ''; // Golește tabelul înainte de a adăuga date noi

        if (clients.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="5" style="text-align: center;">Niciun client găsit.</td>`;
            clientsTableBody.appendChild(row);
            return;
        }

        clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${client.name}</td>
                <td>${client.phone}</td>
                <td>${client.email}</td>
                <td>${client.equipmentCount}</td>
                <td class="actions">
                    <a href="admin_client_detail.html?id=${client.id}">Vezi Detalii</a>
                    <a href="#" class="delete" data-id="${client.id}">Șterge</a>
                </td>
            `;
            clientsTableBody.appendChild(row);
        });
    };

    let currentSort = { key: 'name', direction: 'asc' };
    let currentClients = [...mockClients];

    // --- LOGICA PENTRU SORTARE ---
    const sortClients = (clients, key, direction) => {
        return [...clients].sort((a, b) => {
            let valA = a[key];
            let valB = b[key];

            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return direction === 'asc' ? -1 : 1;
            if (valA > valB) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    };

    // --- LOGICA PENTRU FILTRARE (CĂUTARE) ---
    const filterClients = () => {
        const searchTerm = searchInput.value.toLowerCase();
        const filtered = mockClients.filter(client => {
            return client.name.toLowerCase().includes(searchTerm) ||
                   client.phone.toLowerCase().includes(searchTerm) ||
                   client.email.toLowerCase().includes(searchTerm);
        });

        currentClients = sortClients(filtered, currentSort.key, currentSort.direction);
        displayClients(currentClients);
    };

    // --- ADAUGĂ EVENT LISTENERS ---

    // Listener pentru câmpul de căutare
    searchInput.addEventListener('input', filterClients);

    // Listeners pentru antetele de tabel sortabile
    document.querySelectorAll('.data-table th[data-sort-key]').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.sortKey;

            if (currentSort.key === key) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.direction = 'asc';
            }

            // Re-filtrează și sortează
            filterClients();
        });
    });

    // --- FUNCȚII PENTRU MODALE ---
    const openModal = (modal) => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = (modal) => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    // --- LOGICA PENTRU ADĂUGARE CLIENT ---
    addClientBtn.addEventListener('click', () => {
        addClientForm.reset();
        openModal(addClientModal);
    });

    addClientForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const newClient = {
            id: Date.now(), // ID unic simplu
            name: e.target.elements.name.value,
            phone: e.target.elements.phone.value,
            email: e.target.elements.email.value,
            equipmentCount: 0,
            registerDate: new Date().toISOString().split('T')[0],
        };
        mockClients.push(newClient);
        closeModal(addClientModal);
        filterClients(); // Reafișează tabelul
    });

    // --- LOGICA PENTRU ȘTERGERE CLIENT ---
    clientsTableBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('delete')) {
            e.preventDefault();
            clientIdToDelete = parseInt(e.target.dataset.id, 10);
            openModal(deleteConfirmModal);
        }
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (clientIdToDelete !== null) {
            mockClients = mockClients.filter(client => client.id !== clientIdToDelete);
            clientIdToDelete = null;
            closeModal(deleteConfirmModal);
            filterClients(); // Reafișează tabelul
        }
    });

    // Listener general pentru închiderea modalelor
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            // Închide modalul dacă se dă click pe fundal (adică pe .modal) sau pe un element de închidere
            if (e.target === modal || e.target.closest('[data-micromodal-close]') || e.target.classList.contains('modal-close')) {
                e.preventDefault();
                closeModal(modal);
            }
        });
    });


    // --- Inițializare ---
    filterClients(); // Afișează datele inițiale sortate
});
