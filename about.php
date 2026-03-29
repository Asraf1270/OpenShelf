<?php
/**
 * OpenShelf About Page
 * Information about the platform and team
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .about-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 3rem;
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f172a, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .hero-section p {
            color: var(--text-tertiary);
            max-width: 600px;
            margin: 0 auto;
        }

        .mission-card {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 2rem;
            border-radius: 1.5rem;
            text-align: center;
            margin-bottom: 3rem;
        }

        .mission-card h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .mission-card p {
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #6366f1;
            font-size: 1.5rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .team-card {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .feature-grid {
                grid-template-columns: 1fr;
            }
            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <main>
        <div class="about-page">
            <div class="hero-section">
                <h1>About OpenShelf</h1>
                <p>Building a community of readers, one book at a time</p>
            </div>

            <div class="mission-card">
                <h2>Our Mission</h2>
                <p>To create a vibrant community where students can share books, discover new reads, and connect with fellow book lovers in their residential halls.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">150+</div>
                    <div>Books Shared</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">80+</div>
                    <div>Active Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">45+</div>
                    <div>Books Borrowed</div>
                </div>
            </div>

            <h2 style="text-align: center; margin-bottom: 1.5rem;">What We Offer</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3>Share Books</h3>
                    <p>Add your books to the library and share them with fellow students.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-search"></i></div>
                    <h3>Discover Reads</h3>
                    <p>Browse through thousands of books and find your next favorite read.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fab fa-whatsapp"></i></div>
                    <h3>Connect Directly</h3>
                    <p>Chat with book owners via WhatsApp to arrange pickup.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-star"></i></div>
                    <h3>Write Reviews</h3>
                    <p>Share your thoughts and help others discover great books.</p>
                </div>
            </div>

            <h2 style="text-align: center; margin-bottom: 1.5rem;">Our Team</h2>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-user-graduate"></i></div>
                    <h3>Student Team</h3>
                    <p>Passionate students building a better reading community</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-code"></i></div>
                    <h3>Developers</h3>
                    <p>Building and maintaining the platform</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-heart"></i></div>
                    <h3>Community</h3>
                    <p>All members who make this possible</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>