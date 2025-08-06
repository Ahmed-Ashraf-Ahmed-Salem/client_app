package com.ahmed_ashraf.clientapplication.Repository;

import com.ahmed_ashraf.clientapplication.Entity.LrOfficer;
import com.ahmed_ashraf.clientapplication.Entity.LrOfficerId;
import org.springframework.data.jpa.repository.JpaRepository;

public interface LrOfficerRepository extends JpaRepository<LrOfficer, LrOfficerId> {
}
