<?php
declare(strict_types=1);

final class UserModel
{
    private int $id;
    private string $username;
    private string $email;
    private string $role;
    private string $createdAt;
    private ?string $phone;
    private ?string $profilePhoto;

    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->id = (int) $row['id'];
        $user->username = $row['username'];
        $user->email = $row['email'];
        $user->role = $row['role'];
        $user->createdAt = $row['created_at'];
        $user->phone = isset($row['phone']) && $row['phone'] !== '' ? $row['phone'] : null;
        $user->profilePhoto = isset($row['profile_photo']) && $row['profile_photo'] !== ''
            ? $row['profile_photo']
            : null;
        return $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getProfilePhoto(): ?string
    {
        return $this->profilePhoto;
    }
}
