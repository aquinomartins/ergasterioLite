<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Requests\StoreArtistRequest;
use App\Services\ArtistService;
use App\Services\ArtworkService;

final class ArtistController extends Controller
{
    private ArtistService $artists;
    private ArtworkService $artworks;

    public function __construct()
    {
        $this->artists = new ArtistService();
        $this->artworks = new ArtworkService();
    }

    public function index(): void
    {
        $this->view('artists.index', [
            'pageTitle' => 'Artistas',
            'artists' => $this->artists->listArtists(),
        ]);
    }

    public function show(string $slug): void
    {
        $artist = $this->artists->getArtistBySlug($slug);

        if ($artist === null) {
            http_response_code(404);
            echo 'Artista não encontrado.';
            return;
        }

        $this->view('artists.show', [
            'pageTitle' => $artist['display_name'],
            'artist' => $artist,
            'artworks' => $this->artworks->listByArtist((int) $artist['id']),
        ]);
    }

    public function create(): void
    {
        $this->view('artists.create', [
            'pageTitle' => 'Novo artista',
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function store(): void
    {
        [$errors, $data] = (new StoreArtistRequest())->validate($_POST);

        if ($errors !== []) {
            Session::set('old', $data);
            Session::set('errors', $errors);
            Session::flash('error', 'Revise os dados do artista.');
            $this->redirectTo('/artists/create');
        }

        $artist = $this->artists->createArtist($data, Auth::id());
        Session::flash('success', 'Artista criado com sucesso.');
        $this->redirectTo('/artists/' . $artist['slug']);
    }
}
