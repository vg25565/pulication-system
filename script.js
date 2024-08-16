const menuButton = document.getElementById('menuButton');
const closeSidebar = document.getElementById('closeSidebar');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');

menuButton.addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
  content.classList.toggle('lg:ml-64');
});

closeSidebar.addEventListener('click', () => {
  sidebar.classList.add('-translate-x-full');
  content.classList.remove('lg:ml-64');
});

// function to fetch publication details in table
document.addEventListener('DOMContentLoaded', fetchPublications);

async function fetchPublications() {
    try {
        const response = await fetch('adminScript.php?action=getPublications&id=1'); 
        const data = await response.json();
        const tableBody = document.getElementById('publications-table-body');
        tableBody.innerHTML = '';

        data.forEach(pub => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${pub.faculty_name}</td>
                <td><a href="downloadPublication.php?publication_id=${pub.id}" class="text-blue-500 hover:underline">${pub.title}</a></td>
                <td>${pub.faculty_datetime}</td>
                <td>
                    <button class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600" onclick="updatePublication(${pub.id}, 'approve')">✔</button>
                    <button class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600" onclick="updatePublication(${pub.id}, 'reject')">✘</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    } catch (error) {
        console.error('Error fetching publications:', error);
    }
}

async function fetchFacultyWithoutPublications() {
    try {
        const response = await fetch('getFacultyWithoutPublications.php');
        const data = await response.json();
        const container = document.getElementById('grievances-container');
        const noGrievanceText = document.getElementById('no-grievance-text');
        
        if (data.length === 0) {
            noGrievanceText.textContent = 'No grievances.';
            return;
        }
        
        noGrievanceText.style.display = 'none';

        data.forEach(faculty => {
            const card = document.createElement('div');
            card.className = 'flex items-center justify-between p-4 bg-white shadow-md rounded-md mb-4';
            card.innerHTML = `
                <div>
                    <h2 class="text-lg sm:text-xl font-semibold">${faculty.name}</h2>
                </div>
                <div>
                    <button onclick="sendAlert('${faculty.email}')" class="ml-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Email</button>
                </div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Error fetching faculty data:', error);
    }
}

async function sendAlert(email) {
    try {
        const response = await fetch('sendAlert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `email=${email}&message=You have not published any papers in the last month. Please submit your publications.`
        });

        const result = await response.json();
        if (result.success) {
            alert('Alert email sent successfully.');
        } else {
            alert('Failed to send alert email: ' + result.message);
        }
    } catch (error) {
        console.error('Error sending alert email:', error);
        alert('An error occurred while sending the alert email.');
    }
}

document.addEventListener('DOMContentLoaded', fetchFacultyWithoutPublications);