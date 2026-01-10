document.addEventListener('DOMContentLoaded', () => {
    const clients = [
        { id: 1, name: 'Popescu Ion', email: 'ion.popescu@email.com', phone: '0722123456', car: 'B-123-ABC', subscription: 'Premium', status: 'Activ' },
        { id: 2, name: 'Ionescu Vasile', email: 'vasile.ionescu@email.com', phone: '0745654321', car: 'B-456-XYZ', subscription: 'Standard', status: 'Inactiv' },
        { id: 3, name: 'Georgescu Ana', email: 'ana.georgescu@email.com', phone: '0765112233', car: 'IF-789-QWE', subscription: 'Premium', status: 'Activ' },
    ];

    const tableBody = document.getElementById('clients-table-body');
    const searchInput = document.getElementById('search-clients');
    const addClientBtn = document.getElementById('add-client-btn');
    const addClientModal = document.getElementById('add-client-modal');
    const deleteClientModal = document.getElementById('delete-client-modal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const addClientForm = document.getElementById('add-client-form');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

    let currentSortKey = 'name';
    let currentSortOrder = 'asc';
    let clientToDeleteId = null;

    const renderTable = (clientList) => {
        tableBody.innerHTML = '';
        clientList.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${client.name}</td>
                <td>${client.email}</td>
                <td>${client.phone}</td>
                <td>${client.car}</td>
                <td><span class="status ${client.status.toLowerCase()}">${client.status}</span></td>
                <td>
                    <button class="btn-edit"><i class="fas fa-edit"></i> Editează</button>
                    <button class="btn-delete" data-id="${client.id}"><i class="fas fa-trash"></i> Șterge</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    };

    const sortClients = (key) => {
        if (key === currentSortKey) {
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortKey = key;
            currentSortOrder = 'asc';
        }

        clients.sort((a, b) => {
            if (a[key] < b[key]) return currentSortOrder === 'asc' ? -1 : 1;
            if (a[key] > b[key]) return currentSortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        renderTable(clients);
    };

    const filterClients = (searchTerm) => {
        const lowerCaseSearchTerm = searchTerm.toLowerCase();
        const filtered = clients.filter(client =>
            client.name.toLowerCase().includes(lowerCaseSearchTerm) ||
            client.email.toLowerCase().includes(lowerCaseSearchTerm) ||
            client.car.toLowerCase().includes(lowerCaseSearchTerm)
        );
        renderTable(filtered);
    };

    document.querySelectorAll('th[data-sort-key]').forEach(header => {
        header.addEventListener('click', () => {
            sortClients(header.getAttribute('data-sort-key'));
        });
    });

    searchInput.addEventListener('input', (e) => {
        filterClients(e.target.value);
    });

    addClientBtn.addEventListener('click', () => {
        addClientModal.style.display = 'block';
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            addClientModal.style.display = 'none';
            deleteClientModal.style.display = 'none';
        });
    });

    addClientForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const newClient = {
            id: clients.length + 1,
            name: e.target.name.value,
            email: e.target.email.value,
            phone: e.target.phone.value,
            car: e.target.car.value,
            subscription: 'Standard',
            status: 'Activ'
        };
        clients.push(newClient);
        renderTable(clients);
        addClientForm.reset();
        addClientModal.style.display = 'none';
    });

    tableBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete')) {
            clientToDeleteId = parseInt(e.target.getAttribute('data-id'), 10);
            deleteClientModal.style.display = 'block';
        }
    });

    cancelDeleteBtn.addEventListener('click', () => {
        deleteClientModal.style.display = 'none';
        clientToDeleteId = null;
    });

    confirmDeleteBtn.addEventListener('click', () => {
        const index = clients.findIndex(c => c.id === clientToDeleteId);
        if (index !== -1) {
            clients.splice(index, 1);
        }
        renderTable(clients);
        deleteClientModal.style.display = 'none';
        clientToDeleteId = null;
    });

    window.addEventListener('click', (e) => {
        if (e.target === addClientModal) {
            addClientModal.style.display = 'none';
        }
        if (e.target === deleteClientModal) {
            deleteClientModal.style.display = 'none';
        }
    });

    // Initial render
    renderTable(clients);
});
