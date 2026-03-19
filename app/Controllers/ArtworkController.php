<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Requests\StoreArtworkRequest;
use App\Services\ArtistService;
use App\Services\ArtworkService;

final class ArtworkController extends Controller
{
    private ArtworkService $artworks;
    private ArtistService $artists;

    public function __construct()
    {
        $this->artworks = new ArtworkService();
        $this->artists = new ArtistService();
    }

    public function index(): void
    {
        $this->view('artworks.index', [
            'pageTitle' => 'Obras',
            'artworks' => $this->artworks->listArtworks(),
        ]);
    }

    public function show(string $slug): void
    {
        $artwork = $this->artworks->getArtworkBySlug($slug);

        if ($artwork === null) {
            http_response_code(404);
            echo 'Obra não encontrada.';
            return;
        }

        $this->view('artworks.show', [
            'pageTitle' => $artwork['title'],
            'artwork' => $artwork,
        ]);
    }

    public function create(): void
    {
        $userId = Auth::id();
        $ownedArtists = $userId !== null ? $this->artists->listArtistsByUser($userId) : [];
        $artists = $ownedArtists !== [] ? $ownedArtists : $this->artists->listArtists();

        $this->view('artworks.create', [
            'pageTitle' => 'Nova obra',
            'artists' => $artists,
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function store(): void
    {
        [$errors, $data] = (new StoreArtworkRequest())->validate($_POST, $_FILES);

        if ($errors === []) {
            $result = $this->artworks->createArtwork($data, $_FILES['image'], (int) Auth::id());
            $errors = $result['errors'];
        }

        if ($errors !== []) {
            Session::set('old', $data);
            Session::set('errors', $errors);
            Session::flash('error', 'Não foi possível cadastrar a obra.');
            $this->redirectTo('/artworks/create');
        }

        Session::flash('success', 'Obra criada com sucesso.');
        $this->redirectTo('/artworks/' . $result['artwork']['slug']);
    }
}
