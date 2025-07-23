package com.ahmed_ashraf.clientapplication.Repository;

import com.ahmed_ashraf.clientapplication.Entity.DMClientAppDeleted;
import com.ahmed_ashraf.clientapplication.Entity.DMClientAppDeletedId;
import org.springframework.data.jpa.repository.JpaRepository;

public interface DMClientAppDeletedRepository extends JpaRepository<DMClientAppDeleted, DMClientAppDeletedId> {
}







/*
package com.ahmed_ashraf.clientapplication.Repository;

import com.ahmed_ashraf.clientapplication.Entity.*;
import org.springframework.data.jpa.repository.JpaRepository;

public interface DMClientAppDeletedRepository extends JpaRepository<DMClientAppDeleted, Long> {
}
*/